<?php
namespace Tests\Unit\Services;

use App\Models\User;
use App\Models\UserProgress;
use App\Services\User\LifeRegenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LifeRegenServiceTest extends TestCase
{
    use RefreshDatabase;

    private function makeProgress(int $lives, int $regenAgoHours): UserProgress
    {
        $user = User::factory()->create();
        return UserProgress::create([
            'user_id' => $user->id,
            'lives' => $lives,
            'coins' => 500,
            'last_life_regen_at' => now()->subHours($regenAgoHours),
        ]);
    }

    public function test_no_regen_when_max_lives(): void
    {
        $progress = $this->makeProgress(5, 6);
        (new LifeRegenService())->regenerate($progress);
        $this->assertEquals(5, $progress->refresh()->lives);
    }

    public function test_regen_one_life_after_3_hours(): void
    {
        $progress = $this->makeProgress(3, 3);
        (new LifeRegenService())->regenerate($progress);
        $this->assertEquals(4, $progress->refresh()->lives);
    }

    public function test_regen_multiple_lives(): void
    {
        $progress = $this->makeProgress(1, 9);
        (new LifeRegenService())->regenerate($progress);
        $this->assertEquals(4, $progress->refresh()->lives); // 1 + 3 (9/3), capped at 5
    }

    public function test_capped_at_max(): void
    {
        $progress = $this->makeProgress(4, 9);
        (new LifeRegenService())->regenerate($progress);
        $this->assertEquals(5, $progress->refresh()->lives);
    }
}
