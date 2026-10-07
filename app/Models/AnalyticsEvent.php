<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnalyticsEvent extends Model
{
    protected $table = 'analytics_events';
    protected $fillable = ['user_id', 'event_type', 'payload', 'session_id'];
    protected $casts = ['payload' => 'array'];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}