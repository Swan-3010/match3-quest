<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\SessionLog;
use App\Models\Level;
use App\Models\Purchase;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_users' => User::count(),
            'active_today' => User::where('last_login_at', '>=', now()->subDay())->count(),
            'total_sessions' => SessionLog::count(),
            'win_rate' => SessionLog::where('result', 'win')->count() / max(1, SessionLog::count()) * 100,
            'total_purchases' => Purchase::count(),
            'total_revenue_coins' => Purchase::where('currency', 'coins')->sum('price_paid'),
            'levels_count' => Level::count(),
        ];

        // Top levels by attempts
        $topLevels = SessionLog::select('level_id', DB::raw('COUNT(*) as attempts'), DB::raw('AVG(score) as avg_score'))
            ->groupBy('level_id')
            ->orderByDesc('attempts')
            ->limit(10)
            ->get();

        // DAU last 7 days
        $dau = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $dau[] = [
                'date' => $date,
                'users' => SessionLog::whereDate('created_at', $date)->distinct('user_id')->count(),
            ];
        }

        return view('admin.dashboard', compact('stats', 'topLevels', 'dau'));
    }
}