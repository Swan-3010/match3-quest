<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserAchievement extends Model
{
    protected $table = 'user_achievements';
    protected $fillable = [
        'user_id', 'achievement_id', 'progress_current', 'progress_target',
        'is_completed', 'completed_at'
    ];
    protected $casts = ['is_completed' => 'boolean', 'completed_at' => 'datetime'];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function achievement(): BelongsTo { return $this->belongsTo(Achievement::class); }
}