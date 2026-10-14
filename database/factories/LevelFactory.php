<?php
namespace Database\Factories;

use App\Models\Level;
use Illuminate\Database\Eloquent\Factories\Factory;

class LevelFactory extends Factory
{
    protected $model = Level::class;

    public function definition(): array
    {
        return [
            'level_number' => fake()->unique()->numberBetween(1, 1000),
            'moves_limit' => 23,
            'target_score' => 1500,
            'star_2_score' => 1950,
            'star_3_score' => 2400,
            'colors_count' => 5,
            'config_version' => 1,
            'is_active' => true,
        ];
    }
}
