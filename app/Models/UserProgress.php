<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserProgress extends Model
{
    protected $table = 'user_progress';
    protected $fillable = [
        'user_id', 'max_level_reached', 'total_score', 'total_stars',
        'lives', 'last_life_regen_at', 'current_xp', 'xp_for_next_level',
        'player_level', 'coins', 'gems', 'last_login_at', 'login_streak', 'max_login_streak'
    ];
    protected $casts = [
        'last_life_regen_at' => 'datetime',
        'last_login_at' => 'datetime',
    ];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}