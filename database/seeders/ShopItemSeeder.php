<?php
namespace Database\Seeders;

use App\Models\ShopItem;
use Illuminate\Database\Seeder;

class ShopItemSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['hammer', 'Молот', 'imba', 50, null, 'general', ['destroy' => 1], 'coins', 1],
            ['meteor_row', 'Метеор-ряд', 'imba', 80, null, 'general', ['clear' => 'row'], 'coins', 2],
            ['meteor_col', 'Метеор-столбец', 'imba', 80, null, 'general', ['clear' => 'col'], 'coins', 3],
            ['cross_diag', 'Крест-диагональ', 'imba', 120, null, 'general', ['clear' => 'diag'], 'coins', 4],
            ['shuffle', 'Перемешать', 'imba', 40, null, 'general', ['desc' => 'Перемешать фишки на поле'], 'coins', 5],
            ['extra_moves_5', '+5 ходов', 'boost', 100, null, 'pre_level', ['moves' => 5], 'coins', 6],
            ['start_bomb', 'Старт-бомба', 'boost', 200, null, 'pre_level', ['spawn' => 'bomb'], 'coins', 7],
            ['life_1', '+1 жизнь', 'life', null, 10, 'general', ['qty' => 1, 'desc' => '+1 жизнь'], 'gems', 8],
            ['lives_refill', 'Восстановить все жизни', 'life', null, 25, 'general', ['qty' => 99, 'desc' => 'Восстановить все жизни'], 'gems', 9],
            ['extra_life', '+1 жизнь', 'life', 150, null, 'zero_lives', ['lives' => 1], 'coins', 10],
            ['gems_1', '1 гем', 'gems', 1000, null, 'general', ['qty' => 1, 'desc' => 'Обменять 1000 монет на 1 гем'], 'coins', 11],
        ];
        foreach ($items as $i) {
            ShopItem::updateOrCreate(
                ['code' => $i[0]],
                [
                    'name' => $i[1], 'category' => $i[2], 'price_coins' => $i[3], 'price_gems' => $i[4],
                    'context' => $i[5], 'effect_json' => $i[6], 'price_currency' => $i[7],
                    'is_active' => true, 'sort_order' => $i[8],
                ]
            );
        }
    }
}
