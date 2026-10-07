<?php

namespace Database\Seeders;

use App\Models\Level;
use App\Models\LevelReward;
use Illuminate\Database\Seeder;

class LevelSeeder extends Seeder
{
    public function run(): void
    {
        for ($i = 1; $i <= 50; $i++) {
            $base = 1500;
            $growth = pow($i, 1.35) * 12;
            $target = round($base + $growth);
            $moves = max(15, 23 - intdiv($i, 100) * 2);
            $colors = $i >= 100 ? 6 : 5;

            $level = Level::updateOrCreate(
                ['level_number' => $i],
                [
                    'moves_limit'    => $moves,
                    'target_score'   => $target,
                    'star_2_score'   => round($target * 1.3),
                    'star_3_score'   => round($target * 1.6),
                    'colors_count'   => $colors,
                    'config_version' => 1,
                    'is_active'      => true,
                ]
            );

            foreach ([[1, 'coins', 50], [2, 'coins', 100], [3, 'gems', 1]] as [$type, $code, $qty]) {
                LevelReward::updateOrCreate(
                    ['level_id' => $level->id, 'threshold_type' => $type],
                    ['reward_code' => $code, 'reward_qty' => $qty]
                );
            }
        }
    }
}
