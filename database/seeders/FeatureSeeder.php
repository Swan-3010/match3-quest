<?php
namespace Database\Seeders;

use App\Models\Feature;
use Illuminate\Database\Seeder;

class FeatureSeeder extends Seeder
{
    public function run(): void
    {
        $features = [
            [20, 'rocket', 'Ракета', 'Собрать 4 в линию', ['clear' => 'row'], 'none'],
            [40, 'bomb', 'Бомба', 'Собрать 5 в Г/Т', ['clear' => 'area3x3'], 'none'],
            [60, 'disco', 'Диско-шар', 'Собрать 5 в ряд', ['clear' => 'color'], 'none'],
            [100, 'ice', 'Лёд', 'Препятствие', ['hp' => 1], '5%'],
            [160, 'stone', 'Камень', 'Препятствие', ['hp' => 2], '5%'],
        ];
        foreach ($features as $f) {
            Feature::updateOrCreate(
                ['code' => $f[1]],
                ['unlock_level' => $f[0], 'name' => $f[2], 'unlock_condition' => $f[3], 'effect_json' => $f[4], 'board_impact' => $f[5]]
            );
        }
    }
}