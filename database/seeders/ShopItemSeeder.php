<?php
namespace Database\Seeders;

use App\Models\ShopItem;
use Illuminate\Database\Seeder;

class ShopItemSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['hammer', 'Молот', 'imba', 50, null, 'general', ['destroy' => 1]],
            ['meteor_row', 'Метеор-ряд', 'imba', 80, null, 'general', ['clear' => 'row']],
            ['meteor_col', 'Метеор-столбец', 'imba', 80, null, 'general', ['clear' => 'col']],
            ['cross_diag', 'Крест-диагональ', 'imba', 120, null, 'general', ['clear' => 'diag']],
            ['extra_moves_5', '+5 ходов', 'boost', 100, null, 'pre_level', ['moves' => 5]],
            ['extra_life', '+1 жизнь', 'life', 150, null, 'zero_lives', ['lives' => 1]],
            ['start_bomb', 'Старт-бомба', 'boost', 200, null, 'pre_level', ['spawn' => 'bomb']],
        ];
        foreach ($items as $i) {
            ShopItem::create(['code' => $i[0], 'name' => $i[1], 'category' => $i[2], 'price_coins' => $i[3], 'price_gems' => $i[4], 'context' => $i[5], 'effect_json' => $i[6]]);
        }
    }
}