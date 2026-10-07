<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            LevelSeeder::class,
            FeatureSeeder::class,
            ImbaSeeder::class,
            ComboMatrixSeeder::class,
            AchievementSeeder::class,
            ShopItemSeeder::class,
            GameConfigSeeder::class,
        ]);
    }
}