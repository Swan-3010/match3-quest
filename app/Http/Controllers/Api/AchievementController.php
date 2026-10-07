<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Achievement;
use App\Models\UserAchievement;
use Illuminate\Http\Request;

class AchievementController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $achievements = Achievement::where('is_active', true)->get()->map(function ($ach) use ($user) {
            $ua = UserAchievement::where('user_id', $user->id)->where('achievement_id', $ach->id)->first();
            return [
                'id' => $ach->id,
                'code' => $ach->code,
                'name' => $ach->name,
                'description' => $ach->description,
                'icon' => $ach->icon,
                'progress_current' => $ua ? $ua->progress_current : 0,
                'progress_target' => $ach->condition_value,
                'is_completed' => $ua ? $ua->is_completed : false,
                'reward' => [
                    'type' => $ach->reward_type,
                    'qty' => $ach->reward_qty,
                ],
            ];
        });

        return response()->json($achievements);
    }
}