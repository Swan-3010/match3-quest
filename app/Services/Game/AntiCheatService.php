<?php
namespace App\Services\Game;

use App\Models\Level;

class AntiCheatService
{
    public function validate(Level $level, array $movesLog, int $score, int $movesUsed): bool
    {
        if ($movesUsed > $level->moves_limit) return false;
        $maxScore = $this->calculateMaxPossibleScore($level);
        if ($score > $maxScore * 1.5) return false;
        if (count($movesLog) > $movesUsed) return false; // MVP: лог может быть короче (offline queue)
        return true;
    }

    private function calculateMaxPossibleScore(Level $level): int
    {
        $maxPerMove = 300 * 3;
        return $level->moves_limit * $maxPerMove * 2;
    }
}