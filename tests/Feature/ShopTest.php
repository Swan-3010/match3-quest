<?php
namespace Tests\Feature;

use App\Models\ShopItem;
use App\Models\User;
use App\Models\UserProgress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShopTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        ShopItem::create(['code' => 'hammer', 'name' => 'Молот', 'category' => 'imba', 'price_coins' => 50, 'is_active' => true, 'sort_order' => 1]);
    }

    public function test_list_items(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;
        $res = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/shop/items');
        $res->assertStatus(200)
            ->assertJsonCount(1);
    }

    public function test_purchase_success(): void
    {
        $user = User::factory()->create();
        UserProgress::create(['user_id' => $user->id, 'lives' => 5, 'coins' => 100, 'gems' => 10]);
        $token = $user->createToken('test')->plainTextToken;

        $res = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/shop/purchase', [
                'item_code' => 'hammer',
                'quantity' => 1,
                'currency' => 'coins',
            ]);
        $res->assertStatus(200)
            ->assertJson(['success' => true]);
        $this->assertDatabaseHas('user_inventories', ['user_id' => $user->id, 'imba_code' => 'hammer', 'quantity' => 1]);
        $this->assertDatabaseHas('user_progress', ['user_id' => $user->id, 'coins' => 50]);
    }

    public function test_purchase_insufficient_funds(): void
    {
        $user = User::factory()->create();
        UserProgress::create(['user_id' => $user->id, 'lives' => 5, 'coins' => 10, 'gems' => 0]);
        $token = $user->createToken('test')->plainTextToken;

        $res = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/shop/purchase', [
                'item_code' => 'hammer',
                'quantity' => 1,
                'currency' => 'coins',
            ]);
        $res->assertStatus(402);
    }
}