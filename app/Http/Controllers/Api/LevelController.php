<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Level;
use App\Models\SessionLog;
use App\Services\Game\AntiCheatService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
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
        $level = Level::findOrFail($id);
        $sessionId = Str::uuid()->toString();

        // Store session in cache for 1 hour
        Cache::put("game_session.{$sessionId}", [
            'user_id' => $request->user()->id,
            'level_id' => $id,
            'started_at' => now(),
        ], 3600);

        return response()->json([
            'session_id' => $sessionId,
            'level' => $level,
            'board_seed' => random_int(1, 999999),
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
        ]);

        $level = Level::findOrFail($id);
        $user = $request->user();
        $progress = $user->progress;

        // Anti-cheat (stub)
        $antiCheat = app(AntiCheatService::class);
        if (!$antiCheat->validate($level, $data['moves_log'] ?? [], $data['score'], $data['moves_used'])) {
            return response()->json(['error' => 'Validation failed'], 400);
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
            if ($id > $progress->max_level_reached) {
                $progress->max_level_reached = $id;
            }
            $progress->total_score += $data['score'];
            $progress->total_stars += $data['stars'];
            $progress->coins += floor($data['score'] / 30);
            $progress->save();

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
            $progress->lives = max(0, $progress->lives - 1);
            $progress->save();
            // Still unlock next level per requirements
            if ($id >= $progress->max_level_reached) {
                $progress->max_level_reached = $id + 1;
                $progress->save();
                $nextLevelAvailable = true;
            }
        }

        return response()->json([
            'success' => $data['result'] === 'win',
            'rewards' => $rewards,
            'lives_left' => $progress->lives,
            'next_level_available' => $nextLevelAvailable,
            'new_score' => $progress->total_score,
        ]);
    }
}