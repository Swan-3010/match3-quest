<?php
namespace App\Services\User;

use App\Models\UserProgress;
use Carbon\Carbon;

class LifeRegenService
{
    const MAX_LIVES = 5;
    const REGEN_MINUTES = 180;

    public function regenerate(UserProgress $progress): void
    {
        if ($progress->lives >= self::MAX_LIVES) return;
        if (!$progress->last_life_regen_at) {
            $progress->last_life_regen_at = now();
            $progress->save();
            return;
        }

        $minutesSince = $progress->last_life_regen_at->diffInMinutes(now());
        $livesToAdd = intdiv($minutesSince, self::REGEN_MINUTES);

        if ($livesToAdd > 0) {
            $progress->lives = min(self::MAX_LIVES, $progress->lives + $livesToAdd);
            $progress->last_life_regen_at = $progress->last_life_regen_at->copy()->addMinutes($livesToAdd * self::REGEN_MINUTES);
            $progress->save();
        }
    }

    public function getNextRegenAt(UserProgress $progress): ?Carbon
    {
        if ($progress->lives >= self::MAX_LIVES) return null;
        return $progress->last_life_regen_at->copy()->addMinutes(self::REGEN_MINUTES);
    }
}