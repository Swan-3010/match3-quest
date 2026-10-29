<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\LevelController;
use App\Http\Controllers\Api\ShopController;
use App\Http\Controllers\Api\ConfigController;
use App\Http\Controllers\Api\HelpController;
use App\Http\Controllers\Api\DailyController;
use App\Http\Controllers\Api\AchievementController;
use App\Http\Controllers\Api\AnalyticsController;

Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/auth/reset-password', [AuthController::class, 'resetPassword']);
Route::post('/auth/recover-question', [AuthController::class, 'recoverQuestion']);
Route::post('/auth/recover-reset', [AuthController::class, 'recoverReset']);

// Публичный: нужен до авторизации (экран логина, гостевой режим)
Route::get('/config', [ConfigController::class, 'index']);
Route::get('/help', [HelpController::class, 'index']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::post('/auth/refresh', [AuthController::class, 'refresh']);

    Route::get('/user/profile', [UserController::class, 'profile']);
    Route::get('/user/progress', [UserController::class, 'progress']);
    Route::get('/user/inventory', [UserController::class, 'inventory']);

    Route::get('/levels', [LevelController::class, 'index']);
    Route::get('/levels/map-window', [LevelController::class, 'mapWindow']);
    Route::get('/levels/{id}', [LevelController::class, 'show']);
    Route::post('/levels/{id}/start', [LevelController::class, 'start']);
    Route::post('/levels/{id}/complete', [LevelController::class, 'complete']);

    Route::get('/shop/items', [ShopController::class, 'items']);
    Route::post('/shop/purchase', [ShopController::class, 'purchase']);

    Route::get('/daily/status', [DailyController::class, 'status']);
    Route::post('/daily/claim', [DailyController::class, 'claim']);

    Route::get('/achievements', [AchievementController::class, 'index']);

    Route::post('/analytics/events', [AnalyticsController::class, 'store']);
});