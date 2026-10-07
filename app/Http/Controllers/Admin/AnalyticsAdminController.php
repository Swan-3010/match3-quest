<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SessionLog;
use App\Models\AnalyticsEvent;
use Illuminate\Support\Facades\DB;

class AnalyticsAdminController extends Controller
{
    public function index()
    {
        // Level difficulty (attempts to win)
        $levelDifficulty = SessionLog::select('level_id', 
                DB::raw('COUNT(*) as total_attempts'),
                DB::raw('SUM(CASE WHEN result = "win" THEN 1 ELSE 0 END) as wins'),
                DB::raw('AVG(score) as avg_score'),
                DB::raw('AVG(moves_used) as avg_moves')
            )
            ->groupBy('level_id')
            ->orderBy('level_id')
            ->limit(100)
            ->get()
            ->map(fn($r) => [
                'level_id' => $r->level_id,
                'total_attempts' => $r->total_attempts,
                'wins' => $r->wins,
                'win_rate' => $r->total_attempts > 0 ? round($r->wins / $r->total_attempts * 100, 1) : 0,
                'avg_score' => round($r->avg_score),
                'avg_moves' => round($r->avg_moves, 1),
            ]);

        // Event types count
        $events = AnalyticsEvent::select('event_type', DB::raw('COUNT(*) as count'))
            ->groupBy('event_type')
            ->orderByDesc('count')
            ->limit(20)
            ->get();

        // Retention (simplified)
        $retention = [];
        for ($i = 1; $i <= 7; $i++) {
            $retention[] = [
                'day' => "D{$i}",
                'rate' => random_int(20, 80), // Placeholder until real data
            ];
        }

        return view('admin.analytics', compact('levelDifficulty', 'events', 'retention'));
    }
}