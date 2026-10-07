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
        app(LifeRegenService::class)->regenerate($progress);
        return response()->json($progress);
    }

    public function inventory(Request $request)
    {
        $inventory = $request->user()->inventory;
        return response()->json($inventory);
    }
}