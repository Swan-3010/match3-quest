<?php
namespace App\Console\Commands;

use App\Services\Game\LevelGeneratorService;
use Illuminate\Console\Command;

class GenerateLevels extends Command
{
    protected $signature = 'levels:generate {count=500}';
    protected $description = 'Generate levels via LevelGeneratorService (existing levels are NOT overwritten)';

    public function handle(LevelGeneratorService $generator): void
    {
        $count = (int) $this->argument('count');
        $this->info("Generating {$count} levels...");

        for ($i = 1; $i <= $count; $i++) {
            $generator->generate($i);
        }

        $this->info("Done! {$count} levels ensured.");
    }
}
