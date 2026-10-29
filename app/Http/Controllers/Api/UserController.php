<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\User\LifeRegenService;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function profile(Request $request)
    {
        $user = $request->user()->load('progress', 'inventory', 'achievements.achievement');
        return response()->json($user);
    }

    public function progress(Request $request)
    {
        $progress = $request->user()->progress;
        $regen = app(LifeRegenService::class);
        $regen->regenerate($progress);
        $data = $progress->toArray();
        $data['next_regen_at'] = $regen->getNextRegenAt($progress)?->toIso8601String();
        return response()->json($data);
    }

    public function inventory(Request $request)
    {
        $inventory = $request->user()->inventory;
        return response()->json($inventory);
    }
}