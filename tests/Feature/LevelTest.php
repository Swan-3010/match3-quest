<?php
namespace Tests\Feature;

use App\Models\Level;
use App\Models\User;
use App\Models\UserProgress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LevelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Level::factory()->create(['level_number' => 1, 'moves_limit' => 23, 'target_score' => 1500]);
    }

    public function test_list_levels(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;
        $res = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/levels');
        $res->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_start_level(): void
    {
        $user = User::factory()->create();
        UserProgress::create(['user_id' => $user->id, 'lives' => 5, 'coins' => 500]);
        $token = $user->createToken('test')->plainTextToken;
        $res = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/levels/1/start');
        $res->assertStatus(200)
            ->assertJsonStructure(['session_id', 'level', 'board_seed']);
    }

    public function test_complete_level_win(): void
    {
        $user = User::factory()->create();
        UserProgress::create(['user_id' => $user->id, 'lives' => 5, 'coins' => 500, 'max_level_reached' => 1]);
        $token = $user->createToken('test')->plainTextToken;

        $res = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/levels/1/complete', [
                'session_id' => 'test-session-123',
                'score' => 2000,
                'moves_used' => 20,
                'stars' => 3,
                'result' => 'win',
                'moves_log' => [],
                'client_hash' => 'test',
            ]);
        $res->assertStatus(200)
            ->assertJson(['success' => true, 'next_level_available' => true]);
        $this->assertDatabaseHas('sessions_log', ['user_id' => $user->id, 'result' => 'win']);
    }

    public function test_complete_level_fail(): void
    {
        $user = User::factory()->create();
        UserProgress::create(['user_id' => $user->id, 'lives' => 5, 'coins' => 500, 'max_level_reached' => 1]);
        $token = $user->createToken('test')->plainTextToken;

        $res = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/levels/1/complete', [
                'session_id' => 'test-session-456',
                'score' => 500,
                'moves_used' => 23,
                'stars' => 0,
                'result' => 'fail',
                'moves_log' => [],
                'client_hash' => 'test',
            ]);
        $res->assertStatus(200)
            ->assertJson(['success' => false]);
        $this->assertDatabaseHas('user_progress', ['user_id' => $user->id, 'lives' => 4]);
    }

    public function test_anticheat_rejects_impossible_score(): void
    {
        $user = User::factory()->create();
        UserProgress::create(['user_id' => $user->id, 'lives' => 5, 'coins' => 500]);
        $token = $user->createToken('test')->plainTextToken;

        $res = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/levels/1/complete', [
                'session_id' => 'test-session-789',
                'score' => 999999999,
                'moves_used' => 1,
                'stars' => 3,
                'result' => 'win',
                'moves_log' => [],
                'client_hash' => 'test',
            ]);
        $res->assertStatus(400);
    }
}