<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\LevelAdminController;
use App\Http\Controllers\Admin\FeatureAdminController;
use App\Http\Controllers\Admin\ImbaAdminController;
use App\Http\Controllers\Admin\ShopAdminController;
use App\Http\Controllers\Admin\ConfigAdminController;
use App\Http\Controllers\Admin\UserAdminController;
use App\Http\Controllers\Admin\AnalyticsAdminController;

Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Levels
    Route::get('/levels', [LevelAdminController::class, 'index'])->name('levels.index');
    Route::get('/levels/{id}/edit', [LevelAdminController::class, 'edit'])->name('levels.edit');
    Route::put('/levels/{id}', [LevelAdminController::class, 'update'])->name('levels.update');
    Route::post('/levels/import', [LevelAdminController::class, 'import'])->name('levels.import');
    Route::post('/levels/generate', [LevelAdminController::class, 'generate'])->name('levels.generate');

    // Features
    Route::get('/features', [FeatureAdminController::class, 'index'])->name('features.index');
    Route::get('/features/{id}/edit', [FeatureAdminController::class, 'edit'])->name('features.edit');
    Route::put('/features/{id}', [FeatureAdminController::class, 'update'])->name('features.update');

    // Imbas
    Route::get('/imbas', [ImbaAdminController::class, 'index'])->name('imbas.index');
    Route::get('/imbas/{id}/edit', [ImbaAdminController::class, 'edit'])->name('imbas.edit');
    Route::put('/imbas/{id}', [ImbaAdminController::class, 'update'])->name('imbas.update');

    // Shop
    Route::get('/shop', [ShopAdminController::class, 'index'])->name('shop.index');
    Route::get('/shop/create', [ShopAdminController::class, 'create'])->name('shop.create');
    Route::post('/shop', [ShopAdminController::class, 'store'])->name('shop.store');
    Route::get('/shop/{id}/edit', [ShopAdminController::class, 'edit'])->name('shop.edit');
    Route::put('/shop/{id}', [ShopAdminController::class, 'update'])->name('shop.update');

    // Configs
    Route::get('/configs', [ConfigAdminController::class, 'index'])->name('configs.index');
    Route::get('/configs/{id}/edit', [ConfigAdminController::class, 'edit'])->name('configs.edit');
    Route::put('/configs/{id}', [ConfigAdminController::class, 'update'])->name('configs.update');

    // Users
    Route::get('/users', [UserAdminController::class, 'index'])->name('users.index');
    Route::get('/users/{id}', [UserAdminController::class, 'show'])->name('users.show');

    // Analytics
    Route::get('/analytics', [AnalyticsAdminController::class, 'index'])->name('analytics.index');
});
