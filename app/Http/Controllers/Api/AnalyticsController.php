<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AnalyticsEvent;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'event_type' => 'required|string',
            'payload' => 'nullable|array',
            'session_id' => 'nullable|string',
        ]);

        // Stub: only log 1% of events to avoid DB bloat
        if (random_int(1, 100) <= 1) {
            AnalyticsEvent::create([
                'user_id' => $request->user()?->id,
                'event_type' => $data['event_type'],
                'payload' => $data['payload'] ?? null,
                'session_id' => $data['session_id'] ?? null,
            ]);
        }

        return response()->json(['accepted' => true], 202);
    }
}