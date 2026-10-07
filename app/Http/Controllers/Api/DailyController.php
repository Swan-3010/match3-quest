<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DailyRewardClaim;
use Illuminate\Http\Request;

class DailyController extends Controller
{
    const REWARDS = [
        1 => ['type' => 'coins', 'qty' => 100, 'icon' => '🪙'],
        2 => ['type' => 'coins', 'qty' => 150, 'icon' => '🪙'],
        3 => ['type' => 'imba', 'qty' => 1, 'icon' => '🔨', 'imba_code' => 'hammer'],
        4 => ['type' => 'coins', 'qty' => 250, 'icon' => '🪙'],
        5 => ['type' => 'life', 'qty' => 1, 'icon' => '❤️'],
        6 => ['type' => 'coins', 'qty' => 400, 'icon' => '🪙'],
        7 => ['type' => 'gems', 'qty' => 10, 'icon' => '💎'],
    ];

    public function status(Request $request)
    {
        $user = $request->user();
        $progress = $user->progress;
        $lastClaim = DailyRewardClaim::where('user_id', $user->id)->latest()->first();

        $canClaim = !$lastClaim || $lastClaim->claim_date->lt(now()->startOfDay());
        $streak = $progress->login_streak;
        $day = ($lastClaim ? $lastClaim->day_number : 0) % 7 + 1;

        $calendar = [];
        for ($i = 1; $i <= 7; $i++) {
            $calendar[] = [
                'day' => $i,
                'reward_type' => self::REWARDS[$i]['type'],
                'reward_qty' => self::REWARDS[$i]['qty'],
                'icon' => self::REWARDS[$i]['icon'],
                'is_claimed' => $i < $day || ($i === $day && !$canClaim),
                'is_today' => $i === $day,
            ];
        }

        return response()->json([
            'current_streak' => $streak,
            'max_streak' => $progress->max_login_streak,
            'can_claim_today' => $canClaim,
            'next_reward' => self::REWARDS[$day],
            'calendar' => $calendar,
        ]);
    }

    public function claim(Request $request)
    {
        $user = $request->user();
        $progress = $user->progress;
        $lastClaim = DailyRewardClaim::where('user_id', $user->id)->latest()->first();

        if ($lastClaim && $lastClaim->claim_date->isToday()) {
            return response()->json(['error' => 'Уже получено сегодня'], 429);
        }

        // Check streak continuity
        if ($lastClaim && $lastClaim->claim_date->isYesterday()) {
            $progress->login_streak++;
        } else {
            $progress->login_streak = 1;
        }
        if ($progress->login_streak > $progress->max_login_streak) {
            $progress->max_login_streak = $progress->login_streak;
        }

        $day = ($lastClaim ? $lastClaim->day_number : 0) % 7 + 1;
        $reward = self::REWARDS[$day];

        // Apply reward
        if ($reward['type'] === 'coins') $progress->coins += $reward['qty'];
        elseif ($reward['type'] === 'gems') $progress->gems += $reward['qty'];
        elseif ($reward['type'] === 'life') $progress->lives = min(5, $progress->lives + $reward['qty']);
        $progress->save();

        DailyRewardClaim::create([
            'user_id' => $user->id,
            'day_number' => $day,
            'claim_date' => now(),
        ]);

        return response()->json([
            'reward' => $reward,
            'new_streak' => $progress->login_streak,
            'balance' => ['coins' => $progress->coins, 'gems' => $progress->gems, 'lives' => $progress->lives],
        ]);
    }
}