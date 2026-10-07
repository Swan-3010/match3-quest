<?php
namespace Database\Seeders;

use App\Models\GameConfig;
use Illuminate\Database\Seeder;

class GameConfigSeeder extends Seeder
{
    public function run(): void
    {
        GameConfig::create(['config_key' => 'game_constants', 'config_value' => config('game'), 'version' => 1]);
    }
}