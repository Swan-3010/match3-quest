<?php
namespace Tests\Feature;

use App\Models\GameConfig;
use App\Models\Purchase;
use App\Models\ShopItem;
use App\Models\User;
use App\Models\UserInventory;
use App\Models\UserProgress;
use App\Models\WalletTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WalletShopTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        GameConfig::create([
            'config_key' => 'economy',
            'config_value' => ['max_lives' => 5, 'regen_minutes' => 180, 'gem_exchange_rate' => 1000],
            'version' => 1,
        ]);
        ShopItem::create(['code' => 'hammer', 'name' => 'Молот', 'category' => 'imba', 'price_coins' => 50, 'price_gems' => null, 'price_currency' => 'coins', 'is_active' => true, 'sort_order' => 1]);
        ShopItem::create(['code' => 'life_1', 'name' => '+1 жизнь', 'category' => 'life', 'price_coins' => null, 'price_gems' => 10, 'price_currency' => 'gems', 'effect_json' => ['qty' => 1], 'is_active' => true, 'sort_order' => 2]);
        ShopItem::create(['code' => 'gems_1', 'name' => '1 гем', 'category' => 'gems', 'price_coins' => 1000, 'price_gems' => null, 'price_currency' => 'coins', 'effect_json' => ['qty' => 1], 'is_active' => true, 'sort_order' => 3]);
    }

    private $userId;

    private function tokenFor(array $progressAttrs): string
    {
        $user = User::factory()->create();
        UserProgress::create(array_merge(
            ['user_id' => $user->id, 'lives' => 5, 'coins' => 500, 'gems' => 10],
            $progressAttrs
        ));
        $this->userId = $user->id;
        return $user->createToken('test')->plainTextToken;
    }

    private function purchase(string $token, array $payload): \Illuminate\Testing\TestResponse
    {
        return $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/shop/purchase', $payload);
    }

    public function test_buy_booster_for_coins_writes_ledger(): void
    {
        $token = $this->tokenFor(['coins' => 100, 'gems' => 10]);

        $res = $this->purchase($token, ['item_code' => 'hammer', 'quantity' => 1]);
        $res->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure(['inventory', 'balance', 'client_request_id']);

        $this->assertDatabaseHas('user_progress', ['user_id' => $this->userId, 'coins' => 50]);
        $this->assertDatabaseHas('user_inventories', ['user_id' => $this->userId, 'imba_code' => 'hammer', 'quantity' => 1]);
        $this->assertDatabaseHas('wallet_transactions', [
            'user_id' => $this->userId, 'currency' => 'coins', 'amount' => -50, 'balance_after' => 50, 'type' => 'purchase',
        ]);
        $this->assertDatabaseHas('wallet_transactions', [
            'user_id' => $this->userId, 'currency' => 'booster:hammer', 'amount' => 1, 'balance_after' => 1, 'type' => 'purchase_item',
        ]);
    }

    public function test_buy_life_when_full_rejected_without_changes(): void
    {
        $token = $this->tokenFor(['lives' => 5, 'gems' => 10]);

        $res = $this->purchase($token, ['item_code' => 'life_1', 'quantity' => 1]);
        $res->assertStatus(422)->assertJson(['error' => 'lives_full']);

        $this->assertDatabaseHas('user_progress', ['user_id' => $this->userId, 'lives' => 5, 'gems' => 10]);
        $this->assertDatabaseMissing('wallet_transactions', ['user_id' => $this->userId]);
        $this->assertDatabaseMissing('purchases', ['user_id' => $this->userId]);
    }

    public function test_buy_life_when_full_precedes_insufficient_funds(): void
    {
        // жизни полны и гемов нет — приоритет у lives_full, а не insufficient_funds
        $token = $this->tokenFor(['lives' => 5, 'gems' => 0]);

        $res = $this->purchase($token, ['item_code' => 'life_1', 'quantity' => 1]);
        $res->assertStatus(422)->assertJson(['error' => 'lives_full']);

        $this->assertDatabaseHas('user_progress', ['user_id' => $this->userId, 'lives' => 5, 'gems' => 0]);
        $this->assertDatabaseMissing('wallet_transactions', ['user_id' => $this->userId]);
    }

    public function test_buy_life_spends_gems_and_grants_life(): void
    {
        $token = $this->tokenFor(['lives' => 3, 'gems' => 10]);

        $res = $this->purchase($token, ['item_code' => 'life_1', 'quantity' => 1]);
        $res->assertStatus(200)->assertJsonPath('balance.lives', 4);

        $this->assertDatabaseHas('user_progress', ['user_id' => $this->userId, 'lives' => 4, 'gems' => 0]);
        $this->assertDatabaseHas('wallet_transactions', [
            'user_id' => $this->userId, 'currency' => 'gems', 'amount' => -10, 'balance_after' => 0, 'type' => 'purchase',
        ]);
        $this->assertDatabaseHas('wallet_transactions', [
            'user_id' => $this->userId, 'currency' => 'lives', 'amount' => 1, 'balance_after' => 4, 'type' => 'purchase_item',
        ]);
        $this->assertDatabaseHas('purchases', ['user_id' => $this->userId, 'shop_item_code' => 'life_1', 'price_paid' => 10, 'currency' => 'gems']);
    }

    public function test_buy_gems_for_coins_exchange(): void
    {
        $token = $this->tokenFor(['coins' => 999, 'gems' => 10]);

        $res = $this->purchase($token, ['item_code' => 'gems_1', 'quantity' => 1]);
        $res->assertStatus(402)->assertJson(['error' => 'insufficient_funds']);
        $this->assertDatabaseHas('user_progress', ['user_id' => $this->userId, 'coins' => 999, 'gems' => 10]);
        $this->assertDatabaseMissing('wallet_transactions', ['user_id' => $this->userId]);
        $this->assertDatabaseMissing('purchases', ['user_id' => $this->userId]);

        UserProgress::where('user_id', $this->userId)->update(['coins' => 1000]);
        $res = $this->purchase($token, ['item_code' => 'gems_1', 'quantity' => 1]);
        $res->assertStatus(200)->assertJsonPath('balance.coins', 0)->assertJsonPath('balance.gems', 11);

        $this->assertDatabaseHas('wallet_transactions', [
            'user_id' => $this->userId, 'currency' => 'coins', 'amount' => -1000, 'balance_after' => 0, 'type' => 'purchase',
        ]);
        $this->assertDatabaseHas('wallet_transactions', [
            'user_id' => $this->userId, 'currency' => 'gems', 'amount' => 1, 'balance_after' => 11, 'type' => 'purchase_item',
        ]);
    }

    public function test_insufficient_funds_rejected_without_changes(): void
    {
        $token = $this->tokenFor(['coins' => 10, 'gems' => 0]);

        $res = $this->purchase($token, ['item_code' => 'hammer', 'quantity' => 1]);
        $res->assertStatus(402)->assertJson(['error' => 'insufficient_funds']);

        $this->assertDatabaseHas('user_progress', ['user_id' => $this->userId, 'coins' => 10]);
        $this->assertDatabaseMissing('user_inventories', ['user_id' => $this->userId]);
        $this->assertDatabaseMissing('wallet_transactions', ['user_id' => $this->userId]);
        $this->assertDatabaseMissing('purchases', ['user_id' => $this->userId]);
    }

    public function test_purchase_is_idempotent_by_client_request_id(): void
    {
        $token = $this->tokenFor(['coins' => 100, 'gems' => 10]);
        $payload = ['item_code' => 'hammer', 'quantity' => 1, 'client_request_id' => 'req-abc'];

        $this->purchase($token, $payload)->assertStatus(200);
        $res = $this->purchase($token, $payload);
        $res->assertStatus(200)->assertJsonPath('client_request_id', 'req-abc');

        $this->assertDatabaseHas('user_progress', ['user_id' => $this->userId, 'coins' => 50]);
        $this->assertSame(1, Purchase::where('user_id', $this->userId)->count());
        $this->assertSame(1, WalletTransaction::where('user_id', $this->userId)->where('currency', 'coins')->count());
        $this->assertSame(1, WalletTransaction::where('user_id', $this->userId)->where('currency', 'booster:hammer')->count());
        $this->assertSame(1, UserInventory::where('user_id', $this->userId)->where('imba_code', 'hammer')->value('quantity'));
    }
}
