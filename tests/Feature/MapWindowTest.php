<?php
namespace Tests\Feature;

use App\Models\Level;
use App\Models\User;
use App\Models\UserProgress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MapWindowTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Юзер с прогрессом max_level_reached и (опционально) win-записью за completedLevel.
     */
    private function makeUser(int $maxReached, ?int $completedLevel = null, int $stars = 0): string
    {
        $user = User::factory()->create();
        UserProgress::create([
            'user_id' => $user->id,
            'max_level_reached' => $maxReached,
            'lives' => 5,
            'coins' => 500,
        ]);
        if ($completedLevel !== null) {
            $level = Level::factory()->create(['level_number' => $completedLevel]);
            DB::table('sessions_log')->insert([
                'user_id' => $user->id,
                'level_id' => $level->id,
                'moves_used' => 20,
                'score' => 3000,
                'stars_earned' => $stars,
                'result' => 'win',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        return $user->createToken('test')->plainTextToken;
    }

    private function getWindow(string $token): \Illuminate\Testing\TestResponse
    {
        return $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/levels/map-window');
    }

    /** B9: current=1 → 1–20, узел 1 current, остальные locked */
    public function test_window_at_start(): void
    {
        $token = $this->makeUser(1);

        $res = $this->getWindow($token);
        $res->assertStatus(200)
            ->assertJsonPath('current', 1)
            ->assertJsonPath('from', 1)
            ->assertJsonPath('to', 20);

        $levels = $res->json('levels');
        $this->assertCount(20, $levels);
        $this->assertSame(1, $levels[0]['number']);
        $this->assertSame('current', $levels[0]['status']);
        $this->assertSame('locked', $levels[19]['status']);
        $this->assertSame(20, $levels[19]['number']);
    }

    /** B10: current=5 → 1–20: 1–4 done, 5 current, 6–20 locked */
    public function test_window_current_5(): void
    {
        $token = $this->makeUser(4, 4);

        $res = $this->getWindow($token);
        $res->assertJsonPath('current', 5)
            ->assertJsonPath('from', 1)
            ->assertJsonPath('to', 20);

        $levels = $res->json('levels');
        $this->assertCount(20, $levels);
        $this->assertSame('done', $levels[3]['status']);
        $this->assertSame(4, $levels[3]['number']);
        $this->assertSame('current', $levels[4]['status']);
        $this->assertSame(5, $levels[4]['number']);
        $this->assertSame('locked', $levels[5]['status']);
        $this->assertSame(6, $levels[5]['number']);
    }

    /** B11: current=6 → 1–11 (5 done + current + 5 locked) */
    public function test_window_current_6(): void
    {
        $token = $this->makeUser(5, 5);

        $res = $this->getWindow($token);
        $res->assertJsonPath('current', 6)
            ->assertJsonPath('from', 1)
            ->assertJsonPath('to', 11);

        $levels = $res->json('levels');
        $this->assertCount(11, $levels);
        $this->assertSame(1, $levels[0]['number']);
        $this->assertSame('current', $levels[5]['status']);
        $this->assertSame(6, $levels[5]['number']);
        $this->assertSame('locked', $levels[10]['status']);
        $this->assertSame(11, $levels[10]['number']);
    }

    /** B12: current=30 → 16–35, ровно 14 done перед current, 5 locked после */
    public function test_window_current_30(): void
    {
        $token = $this->makeUser(29, 29);

        $res = $this->getWindow($token);
        $res->assertJsonPath('current', 30)
            ->assertJsonPath('from', 16)
            ->assertJsonPath('to', 35);

        $levels = $res->json('levels');
        $this->assertCount(20, $levels);
        $this->assertSame(16, $levels[0]['number']);
        $this->assertSame('done', $levels[0]['status']);

        $statuses = array_column($levels, 'status');
        $this->assertSame(14, count(array_filter($statuses, fn ($s) => $s === 'done')));
        $this->assertSame('current', $levels[14]['status']);
        $this->assertSame(30, $levels[14]['number']);
        $this->assertSame(5, count(array_filter($statuses, fn ($s) => $s === 'locked')));
        $this->assertSame(35, $levels[19]['number']);
    }

    /** B13+B15: current=51 → 37–56; locked-узлы 51–56 без строк в БД присутствуют, строки не создаются */
    public function test_window_current_51(): void
    {
        $token = $this->makeUser(50, 50);
        $levelsBefore = Level::count();

        $res = $this->getWindow($token);
        $res->assertJsonPath('current', 51)
            ->assertJsonPath('from', 37)
            ->assertJsonPath('to', 56);

        $levels = $res->json('levels');
        $this->assertCount(20, $levels);
        $this->assertSame(37, $levels[0]['number']);
        $this->assertSame('done', $levels[13]['status']);
        $this->assertSame(50, $levels[13]['number']);
        $this->assertSame('current', $levels[14]['status']);
        $this->assertSame(51, $levels[14]['number']);
        $this->assertSame('locked', $levels[15]['status']);
        $this->assertSame(56, $levels[19]['number']);

        // locked-узлы не порождают строк уровней
        $this->assertSame($levelsBefore, Level::count());
    }

    /** B14: звёзды в ответе совпадают с sessions_log (max stars_earned по win) */
    public function test_window_stars_values(): void
    {
        $token = $this->makeUser(5, 5, 3);

        $res = $this->getWindow($token);
        $levels = $res->json('levels');

        $byNumber = collect($levels)->keyBy('number');
        $this->assertSame('done', $byNumber[5]['status']);
        $this->assertSame(3, $byNumber[5]['stars']);
        // у уровня без win-записи звёзд нет
        $this->assertSame(0, $byNumber[1]['stars']);
    }
}
