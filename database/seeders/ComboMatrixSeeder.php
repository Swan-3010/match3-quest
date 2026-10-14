<?php
namespace Database\Seeders;

use App\Models\ComboMatrix;
use Illuminate\Database\Seeder;

class ComboMatrixSeeder extends Seeder
{
    public function run(): void
    {
        $combos = [
            ['ROCKET_H', 'ROCKET_H', ['effect' => 'cross', 'score' => 800]],
            ['ROCKET_H', 'ROCKET_V', ['effect' => 'cross', 'score' => 800]],
            ['ROCKET_H', 'BOMB', ['effect' => 'lines3', 'score' => 1000]],
            ['BOMB', 'BOMB', ['effect' => 'area7x7', 'score' => 1200]],
            ['DISCO', 'ROCKET_H', ['effect' => 'colorToRockets', 'score' => 1500]],
            ['DISCO', 'BOMB', ['effect' => 'colorToBombs', 'score' => 1500]],
            ['DISCO', 'DISCO', ['effect' => 'clearAll', 'score' => 5000]],
        ];
        foreach ($combos as $c) {
            ComboMatrix::updateOrCreate(
                ['special_a' => $c[0], 'special_b' => $c[1]],
                ['resulting_effect_json' => $c[2]]
            );
        }
    }
}