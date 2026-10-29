<?php
namespace Tests\Feature;

use App\Models\GameConfig;
use App\Models\User;
use App\Models\UserProgress;
use App\Services\Game\EconomyConfig;
use App\Services\User\LifeRegenService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EconomyConfigTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function setEconomy(int $regenMinutes): void
    {
        $config = GameConfig::firstOrNew(['config_key' => 'economy']);
        $config->config_value = ['max_lives' => 5, 'regen_minutes' => $regenMinutes, 'gem_exchange_rate' => 1000];
        $config->version = 1;
        $config->save();
    }

    public function test_regen_minutes_comes_from_economy_config(): void
    {
        Carbon::setTestNow('2026-10-29 12:00:00');
        $this->setEconomy(180);

        $user = User::factory()->create();
        $progress = UserProgress::create([
            'user_id' => $user->id,
            'lives' => 4,
            'coins' => 500,
            'last_life_regen_at' => now()->subMinutes(181),
        ]);
        app(LifeRegenService::class)->regenerate($progress);
        $this->assertEquals(5, $progress->refresh()->lives);

        $this->setEconomy(200);
        $this->assertSame(200, EconomyConfig::get('regen_minutes', 180));

        $user = User::factory()->create();
        $progress = UserProgress::create([
            'user_id' => $user->id,
            'lives' => 4,
            'coins' => 500,
            'last_life_regen_at' => now()->subMinutes(181),
        ]);
        app(LifeRegenService::class)->regenerate($progress);
        $this->assertEquals(4, $progress->refresh()->lives);
    }

    public function test_help_endpoint_substitutes_economy_config(): void
    {
        $this->setEconomy(180);

        $res = $this->getJson('/api/v1/help');
        $res->assertStatus(200)->assertJsonStructure(['sections']);
        $sections = collect($res->json('sections'))->keyBy('id');

        $this->assertArrayHasKey('how_to_play', $sections);
        $this->assertArrayHasKey('boosters', $sections);
        $this->assertArrayHasKey('currencies', $sections);
        $this->assertStringContainsString('1000', $sections['currencies']['html']);
        $this->assertStringContainsString('180', $sections['currencies']['html']);
    }
}
