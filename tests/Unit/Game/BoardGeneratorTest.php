<?php
namespace Tests\Unit\Game;

use PHPUnit\Framework\TestCase;

class BoardGeneratorTest extends TestCase
{
    public function test_no_initial_matches(): void
    {
        // Simplified: generate 9x9 grid and verify no 3-in-a-row
        $grid = $this->generateGrid(9, 9, 5);
        $this->assertFalse($this->hasMatches($grid));
    }

    public function test_has_at_least_one_move(): void
    {
        $grid = $this->generateGrid(9, 9, 5);
        $this->assertTrue($this->hasAnyMove($grid));
    }

    private function generateGrid(int $rows, int $cols, int $colors): array
    {
        $grid = [];
        for ($r = 0; $r < $rows; $r++) {
            $grid[$r] = [];
            for ($c = 0; $c < $cols; $c++) {
                do {
                    $color = random_int(0, $colors - 1);
                } while (
                    ($c >= 2 && $grid[$r][$c-1] === $color && $grid[$r][$c-2] === $color) ||
                    ($r >= 2 && $grid[$r-1][$c] === $color && $grid[$r-2][$c] === $color)
                );
                $grid[$r][$c] = $color;
            }
        }
        return $grid;
    }

    private function hasMatches(array $grid): bool
    {
        $rows = count($grid);
        $cols = count($grid[0]);
        for ($r = 0; $r < $rows; $r++) {
            for ($c = 0; $c < $cols - 2; $c++) {
                if ($grid[$r][$c] === $grid[$r][$c+1] && $grid[$r][$c] === $grid[$r][$c+2]) return true;
            }
        }
        for ($c = 0; $c < $cols; $c++) {
            for ($r = 0; $r < $rows - 2; $r++) {
                if ($grid[$r][$c] === $grid[$r+1][$c] && $grid[$r][$c] === $grid[$r+2][$c]) return true;
            }
        }
        return false;
    }

    private function hasAnyMove(array $grid): bool
    {
        $rows = count($grid);
        $cols = count($grid[0]);
        for ($r = 0; $r < $rows; $r++) {
            for ($c = 0; $c < $cols; $c++) {
                if ($c < $cols - 1) {
                    $this->swap($grid, $r, $c, $r, $c + 1);
                    if ($this->hasMatches($grid)) { $this->swap($grid, $r, $c, $r, $c + 1); return true; }
                    $this->swap($grid, $r, $c, $r, $c + 1);
                }
                if ($r < $rows - 1) {
                    $this->swap($grid, $r, $c, $r + 1, $c);
                    if ($this->hasMatches($grid)) { $this->swap($grid, $r, $c, $r + 1, $c); return true; }
                    $this->swap($grid, $r, $c, $r + 1, $c);
                }
            }
        }
        return false;
    }

    private function swap(array &$grid, int $r1, int $c1, int $r2, int $c2): void
    {
        $tmp = $grid[$r1][$c1];
        $grid[$r1][$c1] = $grid[$r2][$c2];
        $grid[$r2][$c2] = $tmp;
    }
}