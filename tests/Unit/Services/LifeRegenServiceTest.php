<?php
namespace Tests\Unit\Services;

use App\Services\User\LifeRegenService;
use App\Models\UserProgress;
use PHPUnit\Framework\TestCase;

class LifeRegenServiceTest extends TestCase
{
    public function test_no_regen_when_max_lives(): void
    {
        $progress = new UserProgress(['lives' => 5, 'last_life_regen_at' => now()->subHours(6)]);
        $service = new LifeRegenService();
        $service->regenerate($progress);
        $this->assertEquals(5, $progress->lives);
    }

    public function test_regen_one_life_after_3_hours(): void
    {
        $progress = new UserProgress(['lives' => 3, 'last_life_regen_at' => now()->subHours(3)]);
        $service = new LifeRegenService();
        $service->regenerate($progress);
        $this->assertEquals(4, $progress->lives);
    }

    public function test_regen_multiple_lives(): void
    {
        $progress = new UserProgress(['lives' => 1, 'last_life_regen_at' => now()->subHours(9)]);
        $service = new LifeRegenService();
        $service->regenerate($progress);
        $this->assertEquals(4, $progress->lives); // 1 + 3 (9/3), capped at 5
    }

    public function test_capped_at_max(): void
    {
        $progress = new UserProgress(['lives' => 4, 'last_life_regen_at' => now()->subHours(9)]);
        $service = new LifeRegenService();
        $service->regenerate($progress);
        $this->assertEquals(5, $progress->lives);
    }
}