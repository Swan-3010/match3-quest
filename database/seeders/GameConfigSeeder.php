<?php
namespace Database\Seeders;

use App\Models\GameConfig;
use Illuminate\Database\Seeder;

class GameConfigSeeder extends Seeder
{
    public function run(): void
    {
        GameConfig::updateOrCreate(
            ['config_key' => 'game_constants'],
            ['config_value' => config('game'), 'version' => 1]
        );

        GameConfig::updateOrCreate(
            ['config_key' => 'economy'],
            ['config_value' => [
                'max_lives' => 5,
                'regen_minutes' => 180,
                'gem_exchange_rate' => 1000,
            ]]
        );
    }
}
