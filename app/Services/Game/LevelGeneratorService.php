<?php
namespace App\Services\Game;

use App\Models\Level;
use App\Models\LevelReward;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class LevelGeneratorService
{
    /**
     * Детерминированная генерация уровня по номеру.
     * Существующая строка никогда не перезаписывается.
     */
    public function generate(int $number): Level
    {
        if ($number < 1) {
            throw new \InvalidArgumentException('Level number must be >= 1, got ' . $number);
        }

        $existing = Level::where('level_number', $number)->first();
        if ($existing) {
            return $existing;
        }

        $attributes = $this->attributes($number);

        try {
            $level = DB::transaction(function () use ($attributes) {
                $level = Level::create($attributes);
                $this->createRewards($level);
                return $level;
            });
        } catch (QueryException $e) {
            // Гонка: параллельный запрос уже вставил строку (unique index level_number)
            $level = Level::where('level_number', $number)->first();
            if (!$level) {
                throw $e;
            }
        }

        Cache::forget('levels.all');

        return $level;
    }

    /**
     * Детерминированный сид поля: одинаков для всех стартов одного уровня.
     * Диапазон и тип совместимы с прежним random_int(1, 999999).
     */
    public function boardSeed(int $number): int
    {
        return hexdec(substr(hash('sha256', "level:{$number}"), 0, 7)) % 999999 + 1;
    }

    private function attributes(int $n): array
    {
        $target = (int) round(1500 + pow($n, 1.35) * 12);
        $moves = max(15, 23 - intdiv($n, 100) * 2);
        $colors = $n >= 400 ? 8 : ($n >= 200 ? 7 : ($n >= 100 ? 6 : 5));

        $obstacles = [];
        if ($n >= 100) $obstacles[] = ['type' => 'ice', 'density' => 4];
        if ($n >= 160) $obstacles[] = ['type' => 'stone', 'density' => 3];
        if ($n >= 240) $obstacles[] = ['type' => 'lock', 'density' => 2];
        if ($n >= 320) $obstacles[] = ['type' => 'generator', 'density' => 1];

        $specials = [];
        if ($n >= 20) $specials[] = 'rocket';
        if ($n >= 40) $specials[] = 'bomb';
        if ($n >= 60) $specials[] = 'disco';

        return [
            'level_number' => $n,
            'moves_limit' => $moves,
            'target_score' => $target,
            'star_2_score' => (int) round($target * 1.3),
            'star_3_score' => (int) round($target * 1.6),
            'obstacles_config' => $obstacles,
            'colors_count' => $colors,
            'special_unlocked' => implode('|', $specials),
            'config_version' => 1,
            'is_active' => true,
        ];
    }

    private function createRewards(Level $level): void
    {
        LevelReward::create(['level_id' => $level->id, 'threshold_type' => 1, 'reward_code' => 'coins', 'reward_qty' => 50]);
        LevelReward::create(['level_id' => $level->id, 'threshold_type' => 2, 'reward_code' => 'coins', 'reward_qty' => 100]);
        LevelReward::create(['level_id' => $level->id, 'threshold_type' => 3, 'reward_code' => 'gems', 'reward_qty' => 1]);
    }
}
