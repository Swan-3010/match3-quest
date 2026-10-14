<?php
namespace Database\Seeders;

use App\Models\Imba;
use Illuminate\Database\Seeder;

class ImbaSeeder extends Seeder
{
    public function run(): void
    {
        $imbas = [
            ['hammer', 'Молот', '3 комбо на уровне', ['destroy' => 1], 50, null],
            ['meteor_row', 'Метеор-ряд', 'Пройти без спецфишек', ['clear' => 'row'], 80, null],
            ['meteor_col', 'Метеор-столбец', '5 матчей подряд', ['clear' => 'col'], 80, null],
            ['cross_diag', 'Крест-диагональ', 'Запас ходов ≥30%', ['clear' => 'diag'], 120, null],
        ];
        foreach ($imbas as $i) {
            Imba::updateOrCreate(
                ['code' => $i[0]],
                ['name' => $i[1], 'unlock_condition' => $i[2], 'effect_json' => $i[3], 'base_price_coins' => $i[4], 'base_price_gems' => $i[5]]
            );
        }
    }
}