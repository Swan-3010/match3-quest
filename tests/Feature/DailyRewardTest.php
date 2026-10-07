<?php
namespace Tests\Feature;

use App\Models\User;
use App\Models\UserProgress;
use App\Models\DailyRewardClaim;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DailyRewardTest extends TestCase
{
    use RefreshDatabase;

    public function test_status_no_claims(): void
    {
        $user = User::factory()->create();
        UserProgress::create(['user_id' => $user->id, 'lives' => 5, 'coins' => 500, 'login_streak' => 0]);
        $token = $user->createToken('test')->plainTextToken;

        $res = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/daily/status');
        $res->assertStatus(200)
            ->assertJson(['can_claim_today' => true, 'current_streak' => 0]);
    }

    public function test_claim_increases_streak(): void
    {
        $user = User::factory()->create();
        UserProgress::create(['user_id' => $user->id, 'lives' => 5, 'coins' => 500, 'login_streak' => 2]);
        $token = $user->createToken('test')->plainTextToken;

        $res = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/daily/claim');
        $res->assertStatus(200)
            ->assertJson(['new_streak' => 3]);
        $this->assertDatabaseHas('daily_reward_claims', ['user_id' => $user->id]);
    }

    public function test_cannot_claim_twice(): void
    {
        $user = User::factory()->create();
        UserProgress::create(['user_id' => $user->id, 'lives' => 5, 'coins' => 500]);
        DailyRewardClaim::create(['user_id' => $user->id, 'day_number' => 1, 'claim_date' => now()]);
        $token = $user->createToken('test')->plainTextToken;

        $res = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/daily/claim');
        $res->assertStatus(429);
    }
}