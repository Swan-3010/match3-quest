<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GameConfig;
use App\Models\Level;
use App\Models\Feature;
use App\Models\Imba;
use App\Models\ComboMatrix;
use App\Models\ShopItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ConfigController extends Controller
{
    public function index(Request $request)
    {
        $clientVersion = (int) $request->header('X-Config-Version', 0);
        $currentVersion = Cache::remember('config.version', 3600, function () {
            return GameConfig::max('version') ?? 1;
        });

        if ($clientVersion >= $currentVersion) {
            return response()->json(['unchanged' => true], 304);
        }

        $config = Cache::remember("game.config.v{$currentVersion}", 3600, function () {
            return [
                'version' => GameConfig::max('version') ?? 1,
                'levels' => Level::where('is_active', true)->get(),
                'features' => Feature::where('is_active', true)->get(),
                'imbas' => Imba::where('is_active', true)->get(),
                'combo_matrix' => ComboMatrix::where('is_active', true)->get(),
                'shop_items' => ShopItem::where('is_active', true)->get(),
                'game_constants' => config('game'),
            ];
        });

        return response()->json($config);
    }
}