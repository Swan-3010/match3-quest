<?php
namespace Tests\Feature;

use App\Models\Level;
use App\Models\ShopItem;
use App\Models\User;
use App\Models\UserProgress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiRegressionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Level::factory()->create(['level_number' => 1, 'moves_limit' => 23, 'target_score' => 1500]);
        ShopItem::create(['code' => 'hammer', 'name' => 'Молот', 'category' => 'imba', 'price_coins' => 50, 'price_gems' => null, 'price_currency' => 'coins', 'is_active' => true, 'sort_order' => 1]);
    }

    private function tokenFor(): string
    {
        $user = User::factory()->create();
        UserProgress::create(['user_id' => $user->id, 'lives' => 5, 'coins' => 500, 'gems' => 10, 'max_level_reached' => 1]);
        return $user->createToken('test')->plainTextToken;
    }

    public function test_start_response_shape_unchanged(): void
    {
        $token = $this->tokenFor();
        $res = $this->withHeader('Authorization', "Bearer {$token}")->postJson('/api/v1/levels/1/start');
        $res->assertStatus(200)
            ->assertJsonStructure(['session_id', 'level', 'board_seed']);
    }

    public function test_complete_response_shape_unchanged(): void
    {
        $token = $this->tokenFor();
        $res = $this->withHeader('Authorization', "Bearer {$token}")->postJson('/api/v1/levels/1/complete', [
            'session_id' => 'regression-session',
            'score' => 2000,
            'moves_used' => 20,
            'stars' => 3,
            'result' => 'win',
            'moves_log' => [],
            'client_hash' => 'test',
        ]);
        $res->assertStatus(200)
            ->assertJsonStructure(['success', 'rewards', 'lives_left', 'next_level_available', 'new_score'])
            ->assertJson(['success' => true, 'next_level_available' => true]);
    }

    public function test_purchase_response_shape_unchanged(): void
    {
        $token = $this->tokenFor();
        $res = $this->withHeader('Authorization', "Bearer {$token}")->postJson('/api/v1/shop/purchase', [
            'item_code' => 'hammer',
            'quantity' => 1,
            'currency' => 'coins',
        ]);
        $res->assertStatus(200)
            ->assertJsonStructure(['inventory', 'balance'])
            ->assertJson(['success' => true])
            ->assertJsonPath('balance.coins', 450);
    }
}
