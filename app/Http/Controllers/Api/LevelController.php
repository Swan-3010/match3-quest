<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Level;
use App\Models\SessionLog;
use App\Models\UserProgress;
use App\Services\Game\AntiCheatService;
use App\Services\Game\EconomyConfig;
use App\Services\Game\LevelGeneratorService;
use App\Services\Game\WalletService;
use App\Services\User\LifeRegenService;
use DomainException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LevelController extends Controller
{
    public function index(Request $request)
    {
        $levels = Cache::remember('levels.all', 3600, function () {
            return Level::where('is_active', true)->get();
        });
        return response()->json($levels);
    }

    public function show(Request $request, $id)
    {
        $level = Cache::remember("level.{$id}", 3600, function () use ($id) {
            return Level::with('rewards')->findOrFail($id);
        });
        return response()->json($level);
    }

    public function start(Request $request, $id)
    {
        $number = (int) $id;
        $level = Level::where('level_number', $number)->first()
            ?? app(LevelGeneratorService::class)->generate($number);

        $progress = $request->user()->progress;
        if ($progress) {
            $regen = app(LifeRegenService::class);
            $regen->regenerate($progress);
            if ($progress->lives < 1) {
                return response()->json([
                    'error' => 'no_lives',
                    'next_regen_at' => $regen->getNextRegenAt($progress)?->toIso8601String(),
                ], 403);
            }
        }

        $sessionId = Str::uuid()->toString();

        // Store session in cache for 1 hour
        Cache::put("game_session.{$sessionId}", [
            'user_id' => $request->user()->id,
            'level_id' => $level->id,
            'started_at' => now(),
        ], 3600);

        return response()->json([
            'session_id' => $sessionId,
            'level' => $level,
            'board_seed' => app(LevelGeneratorService::class)->boardSeed($number),
        ]);
    }

    public function mapWindow(Request $request)
    {
        $user = $request->user();
        $reached = (int) ($user->progress->max_level_reached ?? 1);

        // current — первый непройденный: если уровень `reached` пройден, текущий = reached + 1
        $reachedCompleted = DB::table('sessions_log')
            ->join('levels', 'levels.id', '=', 'sessions_log.level_id')
            ->where('sessions_log.user_id', $user->id)
            ->where('sessions_log.result', 'win')
            ->where('levels.level_number', $reached)
            ->exists();
        $current = $reachedCompleted ? $reached + 1 : $reached;

        if ($current <= 5) {
            $from = 1;
            $to = 20;
        } else {
            $from = max(1, $current - 14);
            $to = $current + 5;
        }

        $stars = DB::table('sessions_log')
            ->join('levels', 'levels.id', '=', 'sessions_log.level_id')
            ->where('sessions_log.user_id', $user->id)
            ->where('sessions_log.result', 'win')
            ->whereBetween('levels.level_number', [$from, $current - 1])
            ->groupBy('levels.level_number')
            ->selectRaw('levels.level_number, MAX(sessions_log.stars_earned) as stars')
            ->pluck('stars', 'levels.level_number');

        $levels = [];
        for ($n = $from; $n <= $to; $n++) {
            $status = $n < $current ? 'done' : ($n === $current ? 'current' : 'locked');
            $levels[] = [
                'number' => $n,
                'status' => $status,
                'stars' => $status === 'done' ? (int) ($stars[$n] ?? 0) : 0,
            ];
        }

        return response()->json([
            'current' => $current,
            'from' => $from,
            'to' => $to,
            'levels' => $levels,
        ]);
    }

    public function complete(Request $request, $id)
    {
        $data = $request->validate([
            'session_id' => 'required|string',
            'score' => 'required|integer|min:0',
            'moves_used' => 'required|integer|min:0',
            'stars' => 'required|integer|between:0,3',
            'result' => 'required|in:win,fail',
            'moves_log' => 'nullable|array',
            'client_hash' => 'nullable|string',
            'boosters_used' => 'nullable|array',
            'boosters_used.*' => 'string',
        ]);

        return DB::transaction(function () use ($request, $id, $data) {
            $level = Level::findOrFail($id);
            $user = $request->user();
            $progress = UserProgress::where('user_id', $user->id)->lockForUpdate()->firstOrFail();
            $wallet = app(WalletService::class);

            app(LifeRegenService::class)->regenerate($progress);

            // Anti-cheat (stub)
            $antiCheat = app(AntiCheatService::class);
            if (!$antiCheat->validate($level, $data['moves_log'] ?? [], $data['score'], $data['moves_used'])) {
                return response()->json(['error' => 'Validation failed'], 400);
            }

            // Списание бустеров до записи сессии: при нехватке — без изменений
            foreach ($data['boosters_used'] ?? [] as $code) {
                try {
                    $wallet->apply($progress, 'booster:'.$code, -1, 'booster_used', 'level', $level->id);
                } catch (DomainException $e) {
                    return response()->json(['error' => 'insufficient_boosters', 'booster' => $code], 400);
                }
            }

            // Save session log
            SessionLog::create([
                'user_id' => $user->id,
                'level_id' => $id,
                'moves_used' => $data['moves_used'],
                'score' => $data['score'],
                'stars_earned' => $data['stars'],
                'result' => $data['result'],
                'moves_log' => $data['moves_log'],
                'client_hash' => $data['client_hash'],
            ]);

            $rewards = [];
            $nextLevelAvailable = false;

            if ($data['result'] === 'win') {
                // Update progress
                $progress->max_level_reached = max($progress->max_level_reached, $id);
                $progress->total_score += $data['score'];
                $progress->total_stars += $data['stars'];
                $progress->save();

                $wallet->apply($progress, 'coins', (int) floor($data['score'] / 30), 'level_reward', 'level', $level->id);

                $nextLevelAvailable = true;

                // Check rewards
                foreach ($level->rewards as $reward) {
                    if ($data['stars'] >= $reward->threshold_type) {
                        $rewards[] = [
                            'type' => $reward->reward_code,
                            'qty' => $reward->reward_qty,
                        ];
                    }
                }
            } else {
                // Fail: -1 life
                $wallet->apply($progress, 'lives', -1, 'life_loss', 'level', $level->id);
                $progress = UserProgress::where('id', $progress->id)->lockForUpdate()->firstOrFail();
                // фикс бага A: жизнь не должна вернуться мгновенно
                if ($progress->lives < (int) EconomyConfig::get('max_lives', 5)) {
                    $progress->last_life_regen_at = now();
                    $progress->save();
                }
                // фикс бага B: поражение не открывает следующий уровень
            }

            return response()->json([
                'success' => $data['result'] === 'win',
                'rewards' => $rewards,
                'lives_left' => $progress->lives,
                'next_level_available' => $nextLevelAvailable,
                'new_score' => $progress->total_score,
            ]);
        });
    }
}