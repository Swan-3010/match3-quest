<?php
namespace App\Services\User;

use App\Models\UserProgress;
use App\Services\Game\EconomyConfig;
use Carbon\Carbon;

class LifeRegenService
{
    public function maxLives(): int
    {
        return (int) EconomyConfig::get('max_lives', 5);
    }

    public function regenMinutes(): int
    {
        return (int) EconomyConfig::get('regen_minutes', 180);
    }

    public function regenerate(UserProgress $progress): void
    {
        $maxLives = $this->maxLives();
        if ($progress->lives >= $maxLives) return;
        if (!$progress->last_life_regen_at) {
            $progress->last_life_regen_at = now();
            $progress->save();
            return;
        }

        $minutesSince = $progress->last_life_regen_at->diffInMinutes(now());
        $livesToAdd = intdiv($minutesSince, $this->regenMinutes());

        if ($livesToAdd > 0) {
            $progress->lives = min($maxLives, $progress->lives + $livesToAdd);
            $progress->last_life_regen_at = $progress->last_life_regen_at->copy()->addMinutes($livesToAdd * $this->regenMinutes());
            $progress->save();
        }
    }

    public function getNextRegenAt(UserProgress $progress): ?Carbon
    {
        if ($progress->lives >= $this->maxLives()) return null;
        if (!$progress->last_life_regen_at) return null;
        return $progress->last_life_regen_at->copy()->addMinutes($this->regenMinutes());
    }
}
