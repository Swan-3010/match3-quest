<?php
namespace App\Console\Commands;

use App\Models\Level;
use App\Models\LevelReward;
use Illuminate\Console\Command;

class GenerateLevels extends Command
{
    protected $signature = 'levels:generate {count=500}';
    protected $description = 'Generate levels from resource tables';

    public function handle(): void
    {
        $count = (int) $this->argument('count');
        $this->info("Generating {$count} levels...");

        for ($i = 1; $i <= $count; $i++) {
            $base = 1500;
            $growth = pow($i, 1.35) * 12;
            $target = round($base + $growth);
            $moves = max(15, 23 - intdiv($i, 100) * 2);
            $colors = $i >= 400 ? 8 : ($i >= 200 ? 7 : ($i >= 100 ? 6 : 5));

            $obstacles = [];
            if ($i >= 100) $obstacles[] = ['type' => 'ice', 'density' => 5];
            if ($i >= 160) $obstacles[] = ['type' => 'stone', 'density' => 5];
            if ($i >= 240) $obstacles[] = ['type' => 'lock', 'density' => 5];
            if ($i >= 320) $obstacles[] = ['type' => 'generator', 'density' => 3];

            $specials = [];
            if ($i >= 20) $specials[] = 'rocket';
            if ($i >= 40) $specials[] = 'bomb';
            if ($i >= 60) $specials[] = 'disco';

            $level = Level::updateOrCreate(
                ['level_number' => $i],
                [
                    'moves_limit' => $moves,
                    'target_score' => $target,
                    'star_2_score' => round($target * 1.3),
                    'star_3_score' => round($target * 1.6),
                    'obstacles_config' => $obstacles,
                    'colors_count' => $colors,
                    'special_unlocked' => implode('|', $specials),
                    'config_version' => 1,
                    'is_active' => true,
                ]
            );

            // Rewards
            LevelReward::where('level_id', $level->id)->delete();
            LevelReward::create(['level_id' => $level->id, 'threshold_type' => 1, 'reward_code' => 'coins', 'reward_qty' => 50]);
            LevelReward::create(['level_id' => $level->id, 'threshold_type' => 2, 'reward_code' => 'coins', 'reward_qty' => 100]);
            LevelReward::create(['level_id' => $level->id, 'threshold_type' => 3, 'reward_code' => 'gems', 'reward_qty' => 1]);
        }

        $this->info("Done! {$count} levels generated.");
    }
}