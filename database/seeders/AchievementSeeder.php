<?php
namespace Database\Seeders;

use App\Models\Achievement;
use Illuminate\Database\Seeder;

class AchievementSeeder extends Seeder
{
    public function run(): void
    {
        $achs = [
            ['first_blood', 'Первый матч', 'Собрать первый матч', 'match_count', 1, 'coins', 50, '🎯'],
            ['combo_starter', 'Комбо-мастер', '10 комбо подряд', 'combo_streak', 10, 'coins', 200, '🔥'],
            ['level_10', 'Новичок', 'Пройти 10 уровней', 'levels_completed', 10, 'coins', 500, '🥉'],
            ['level_50', 'Опытный', 'Пройти 50 уровней', 'levels_completed', 50, 'gems', 10, '🥈'],
            ['star_collector', 'Звёздочёт', '100 звёзд', 'stars_total', 100, 'coins', 1000, '⭐'],
        ];
        foreach ($achs as $a) {
            Achievement::create(['code' => $a[0], 'name' => $a[1], 'description' => $a[2], 'condition_type' => $a[3], 'condition_value' => $a[4], 'reward_type' => $a[5], 'reward_qty' => $a[6], 'icon' => $a[7]]);
        }
    }
}