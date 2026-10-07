<?php
namespace Tests\Unit\Game;

use PHPUnit\Framework\TestCase;

class ScoreCalculatorTest extends TestCase
{
    public function test_match_3_base_score(): void
    {
        $this->assertEquals(60, $this->calculateScore(3, 1));
    }

    public function test_match_4_creates_rocket(): void
    {
        $this->assertEquals(150, $this->calculateScore(4, 1));
    }

    public function test_match_5_creates_disco(): void
    {
        $this->assertEquals(300, $this->calculateScore(5, 1));
    }

    public function test_cascade_bonus(): void
    {
        $base = 60;
        $cascade = 3;
        $expected = (int) ($base * (1 + (3 - 1) * 0.2));
        $this->assertEquals($expected, $this->calculateScore(3, 3));
    }

    private function calculateScore(int $matchLength, int $cascade): int
    {
        $base = match(true) {
            $matchLength >= 5 => 300,
            $matchLength === 4 => 150,
            default => 60,
        };
        $multiplier = 1 + ($cascade - 1) * 0.2;
        return (int) ($base * $multiplier);
    }
}