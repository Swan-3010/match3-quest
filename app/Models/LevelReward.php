<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LevelReward extends Model
{
    protected $table = 'level_rewards';
    protected $fillable = ['level_id', 'threshold_type', 'reward_code', 'reward_qty'];

    public function level(): BelongsTo { return $this->belongsTo(Level::class); }
}