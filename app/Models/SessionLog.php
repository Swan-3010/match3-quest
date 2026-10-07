<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SessionLog extends Model
{
    protected $table = 'sessions_log';
    protected $fillable = [
        'user_id', 'level_id', 'moves_used', 'score', 'stars_earned',
        'result', 'duration_seconds', 'moves_log', 'client_hash'
    ];
    protected $casts = ['moves_log' => 'array'];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}