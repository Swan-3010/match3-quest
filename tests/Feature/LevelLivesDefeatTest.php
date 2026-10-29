<?php
namespace Tests\Feature;

use App\Models\GameConfig;
use App\Models\Level;
use App\Models\User;
use App\Models\UserInventory;
use App\Models\UserProgress;
use App\Models\WalletTransaction;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LevelLivesDefeatTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-29 12:00:00');
        GameConfig::create([
            'config_key' => 'economy',
            'config_value' => ['max_lives' => 5, 'regen_minutes' => 180, 'gem_exchange_rate' => 1000],
            'version' => 1,
        ]);
        Level::factory()->create(['level_number' => 1, 'moves_limit' => 23, 'target_score' => 1500]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function tokenFor(array $progressAttrs): string
    {
        $user = User::factory()->create();
        UserProgress::create(array_merge(
            ['user_id' => $user->id, 'lives' => 5, 'coins' => 500, 'max_level_reached' => 1, 'last_life_regen_at' => now()],
            $progressAttrs
        ));
        $this->userId = $user->id;
        return $user->createToken('test')->plainTextToken;
    }

    private $userId;

    private function complete(string $token, int $levelId, array $overrides = []): \Illuminate\Testing\TestResponse
    {
        return $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/v1/levels/{$levelId}/complete", array_merge([
                'session_id' => 'session-'.uniqid(),
                'score' => 500,
                'moves_used' => 23,
                'stars' => 0,
                'result' => 'fail',
                'moves_log' => [],
                'client_hash' => 'test',
            ], $overrides));
    }

    public function test_defeat_loses_life_and_regen_does_not_restore_instantly(): void
    {
        $token = $this->tokenFor(['last_life_regen_at' => now()->subMinutes(400)]);

        $res = $this->complete($token, 1);
        $res->assertStatus(200)->assertJson(['success' => false, 'lives_left' => 4]);
        $this->assertDatabaseHas('user_progress', ['user_id' => $this->userId, 'lives' => 4]);

        // повторный progress: реген не вернул жизнь, next_regen_at в будущем
        $res = $this->withHeader('Authorization', "Bearer {$token}")->getJson('/api/v1/user/progress');
        $res->assertStatus(200)
            ->assertJsonPath('lives', 4)
            ->assertJsonPath('next_regen_at', now()->addMinutes(180)->toIso8601String());

        // каждое поражение −1
        $res = $this->complete($token, 1);
        $res->assertStatus(200)->assertJsonPath('lives_left', 3);
        $this->assertDatabaseHas('user_progress', ['user_id' => $this->userId, 'lives' => 3]);

        // победа жизни не тратит
        $res = $this->complete($token, 1, ['result' => 'win', 'score' => 2000, 'moves_used' => 20, 'stars' => 3]);
        $res->assertStatus(200)->assertJsonPath('lives_left', 3);
        $this->assertDatabaseHas('user_progress', ['user_id' => $this->userId, 'lives' => 3]);

        // за списания жизней есть ledger-записи
        $this->assertSame(2, WalletTransaction::where('user_id', $this->userId)->where('currency', 'lives')->where('amount', -1)->where('type', 'life_loss')->count());
    }

    public function test_defeat_does_not_unlock_next_level(): void
    {
        $level2 = Level::factory()->create(['level_number' => 2, 'moves_limit' => 23, 'target_score' => 1500]);
        $token = $this->tokenFor(['total_score' => 0, 'total_stars' => 0]);

        $res = $this->complete($token, 1);
        $res->assertStatus(200)->assertJson(['success' => false, 'next_level_available' => false, 'new_score' => 0]);
        $this->assertDatabaseHas('user_progress', [
            'user_id' => $this->userId,
            'max_level_reached' => 1,
            'total_score' => 0,
            'total_stars' => 0,
        ]);

        $res = $this->complete($token, $level2->id, ['result' => 'win', 'score' => 2000, 'moves_used' => 20, 'stars' => 3]);
        $res->assertStatus(200)->assertJson(['success' => true, 'next_level_available' => true, 'new_score' => 2000]);
        $this->assertDatabaseHas('user_progress', [
            'user_id' => $this->userId,
            'max_level_reached' => $level2->id,
            'total_score' => 2000,
            'total_stars' => 3,
        ]);
    }

    public function test_start_with_zero_lives_returns_403(): void
    {
        $token = $this->tokenFor(['lives' => 0, 'last_life_regen_at' => now()]);

        $res = $this->withHeader('Authorization', "Bearer {$token}")->postJson('/api/v1/levels/1/start');
        $res->assertStatus(403)
            ->assertJson(['error' => 'no_lives'])
            ->assertJsonPath('next_regen_at', now()->addMinutes(180)->toIso8601String());
    }

    public function test_boosters_used_spends_inventory(): void
    {
        $token = $this->tokenFor([]);
        UserInventory::create(['user_id' => $this->userId, 'imba_code' => 'hammer', 'quantity' => 1]);

        $res = $this->complete($token, 1, ['result' => 'win', 'score' => 2000, 'moves_used' => 20, 'stars' => 3, 'boosters_used' => ['hammer']]);
        $res->assertStatus(200)->assertJson(['success' => true]);
        $this->assertSame(0, UserInventory::where('user_id', $this->userId)->where('imba_code', 'hammer')->value('quantity'));
        $this->assertDatabaseHas('wallet_transactions', [
            'user_id' => $this->userId, 'currency' => 'booster:hammer', 'amount' => -1, 'balance_after' => 0, 'type' => 'booster_used',
        ]);
    }

    public function test_boosters_used_without_inventory_rejected_without_changes(): void
    {
        $token = $this->tokenFor([]);

        $res = $this->complete($token, 1, ['result' => 'win', 'score' => 2000, 'moves_used' => 20, 'stars' => 3, 'boosters_used' => ['hammer']]);
        $res->assertStatus(400)->assertJson(['error' => 'insufficient_boosters', 'booster' => 'hammer']);

        $this->assertDatabaseMissing('sessions_log', ['user_id' => $this->userId]);
        $this->assertDatabaseMissing('wallet_transactions', ['user_id' => $this->userId]);
        $this->assertDatabaseHas('user_progress', ['user_id' => $this->userId, 'lives' => 5, 'coins' => 500]);
        $this->assertDatabaseMissing('user_inventories', ['user_id' => $this->userId]);
    }
}
