<?php
namespace Tests\Feature;

use App\Models\Level;
use App\Models\User;
use App\Models\UserProgress;
use App\Services\Game\LevelGeneratorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OnDemandLevelsTest extends TestCase
{
    use RefreshDatabase;

    private function authUser(array $progressAttrs = []): array
    {
        $user = User::factory()->create();
        UserProgress::create(array_merge(
            ['user_id' => $user->id, 'lives' => 5, 'coins' => 500],
            $progressAttrs
        ));
        $token = $user->createToken('test')->plainTextToken;
        return [$user, $token];
    }

    private function postStart(string $token, int $n): \Illuminate\Testing\TestResponse
    {
        return $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/v1/levels/{$n}/start");
    }

    private function seedLevels(int $count): void
    {
        $generator = app(LevelGeneratorService::class);
        for ($i = 1; $i <= $count; $i++) {
            $generator->generate($i);
        }
    }

    /** A1: старт 51 на чистой БД с 50 уровнями → 200, строка создана */
    public function test_level_51_start_returns_200_after_fix(): void
    {
        $this->seedLevels(50);
        [, $token] = $this->authUser();

        $res = $this->postStart($token, 51);
        $res->assertStatus(200)->assertJsonStructure(['session_id', 'level', 'board_seed']);
        $this->assertDatabaseHas('levels', ['level_number' => 51]);
    }

    /** A2: два последовательных старта 51 → идентичный board_seed, одна строка */
    public function test_on_demand_generation_is_deterministic(): void
    {
        [, $token] = $this->authUser();

        $s1 = $this->postStart($token, 51);
        $s2 = $this->postStart($token, 51);
        $s1->assertStatus(200);
        $s2->assertStatus(200);

        $this->assertSame($s1->json('board_seed'), $s2->json('board_seed'));
        $this->assertSame(
            app(LevelGeneratorService::class)->boardSeed(51),
            $s1->json('board_seed')
        );
        $this->assertSame(1, Level::where('level_number', 51)->count());
    }

    /** A3: повторные старты 51 → ровно одна строка уровня и одна тройка наград, без 500 */
    public function test_concurrent_generation_no_duplicate(): void
    {
        [, $token] = $this->authUser();

        for ($i = 0; $i < 3; $i++) {
            $this->postStart($token, 51)->assertStatus(200);
        }

        $this->assertSame(1, Level::where('level_number', 51)->count());
        $levelId = Level::where('level_number', 51)->value('id');
        $this->assertSame(3, DB::table('level_rewards')->where('level_id', $levelId)->count());
    }

    /** A4: существующие уровни 1–50 не изменяются сервисными стартами 51–60 */
    public function test_existing_levels_not_modified(): void
    {
        $this->seedLevels(60);
        $snapshot = $this->snapshot(1, 50);

        [, $token] = $this->authUser();
        for ($n = 51; $n <= 60; $n++) {
            $this->postStart($token, $n)->assertStatus(200);
        }

        $this->assertSame($snapshot, $this->snapshot(1, 50));
    }

    /** A5: формулы сервиса дают legacy-значения 1–50 (фикстура снята с prod-подобной БД) */
    public function test_formula_matches_legacy_for_1_50(): void
    {
        $this->seedLevels(50);

        $legacy = json_decode(
            file_get_contents(base_path('tests/Fixtures/levels_legacy_1_50.json')),
            true
        );
        $this->assertCount(50, $legacy);

        foreach ($legacy as $row) {
            $level = Level::where('level_number', $row['level_number'])->firstOrFail();
            $msg = 'level ' . $row['level_number'];
            $this->assertSame((int) $row['moves_limit'], $level->moves_limit, $msg);
            $this->assertSame((int) $row['target_score'], $level->target_score, $msg);
            $this->assertSame((int) $row['star_2_score'], $level->star_2_score, $msg);
            $this->assertSame((int) $row['star_3_score'], $level->star_3_score, $msg);
            $this->assertSame((int) $row['colors_count'], $level->colors_count, $msg);
            $this->assertSame($row['special_unlocked'], $level->special_unlocked, $msg);
            $this->assertSame(
                json_decode($row['obstacles_config'], true),
                $level->obstacles_config,
                $msg
            );
            $this->assertTrue($level->is_active, $msg);
        }
    }

    /** A6: клампы на больших номерах: colors ≤ 8, moves ≥ 15, target положительный, density ≤ 10% */
    public function test_high_level_clamps(): void
    {
        $generator = app(LevelGeneratorService::class);

        foreach ([100, 500, 1000, 10000] as $n) {
            $level = $generator->generate($n);
            $this->assertLessThanOrEqual(8, $level->colors_count, "colors at {$n}");
            $this->assertGreaterThanOrEqual(15, $level->moves_limit, "moves at {$n}");
            $this->assertGreaterThan(0, $level->target_score, "target at {$n}");
            $this->assertLessThan(10 ** 9, $level->target_score, "target at {$n}");
            $density = array_sum(array_column($level->obstacles_config ?? [], 'density'));
            $this->assertLessThanOrEqual(10, $density, "obstacle density at {$n}");
        }
    }

    /** A7: формат ответа start обратно совместим — прежние поля, прежние типы */
    public function test_api_response_backward_compatible(): void
    {
        $this->seedLevels(1);
        [, $token] = $this->authUser();

        $res = $this->postStart($token, 1);
        $res->assertStatus(200);
        $json = $res->json();

        $this->assertSame(['session_id', 'level', 'board_seed'], array_keys($json));
        $this->assertIsString($json['session_id']);
        $this->assertIsInt($json['board_seed']);
        $this->assertGreaterThanOrEqual(1, $json['board_seed']);
        $this->assertLessThanOrEqual(999999, $json['board_seed']);
        $this->assertIsInt($json['level']['moves_limit']);
        $this->assertIsInt($json['level']['target_score']);
        $this->assertIsInt($json['level']['colors_count']);
        $this->assertIsInt($json['level']['level_number']);
        $this->assertArrayHasKey('star_2_score', $json['level']);
        $this->assertArrayHasKey('star_3_score', $json['level']);
        $this->assertArrayHasKey('obstacles_config', $json['level']);
        $this->assertArrayHasKey('special_unlocked', $json['level']);
    }

    /** A8: прогресс, звёзды и токен на месте; после прохождения 50 current == 51 */
    public function test_user_progress_preserved_and_current_is_51(): void
    {
        $this->seedLevels(50);
        [$user, $token] = $this->authUser(['max_level_reached' => 50]);
        $level50 = Level::where('level_number', 50)->firstOrFail();

        DB::table('sessions_log')->insert([
            'user_id' => $user->id,
            'level_id' => $level50->id,
            'moves_used' => 20,
            'score' => 5000,
            'stars_earned' => 3,
            'result' => 'win',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // миграции этого ТЗ отсутствуют; данные не теряются
        $this->assertDatabaseHas('user_progress', ['user_id' => $user->id, 'max_level_reached' => 50]);
        $this->assertDatabaseHas('personal_access_tokens', ['tokenable_id' => $user->id]);
        $this->assertDatabaseHas('sessions_log', ['user_id' => $user->id, 'result' => 'win']);

        $res = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/levels/map-window');
        $res->assertStatus(200)->assertJsonPath('current', 51);
    }

    private function snapshot(int $from, int $to): array
    {
        return Level::whereBetween('level_number', [$from, $to])
            ->orderBy('level_number')
            ->get(['level_number', 'moves_limit', 'target_score', 'star_2_score', 'star_3_score',
                'obstacles_config', 'colors_count', 'special_unlocked', 'is_active'])
            ->map(function (Level $l) {
                return [
                    'level_number' => $l->level_number,
                    'moves_limit' => $l->moves_limit,
                    'target_score' => $l->target_score,
                    'star_2_score' => $l->star_2_score,
                    'star_3_score' => $l->star_3_score,
                    'obstacles_config' => json_encode($l->obstacles_config),
                    'colors_count' => $l->colors_count,
                    'special_unlocked' => $l->special_unlocked,
                    'is_active' => (bool) $l->is_active,
                ];
            })
            ->all();
    }
}
