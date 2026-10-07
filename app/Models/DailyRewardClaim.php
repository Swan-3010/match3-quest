<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyRewardClaim extends Model
{
    protected $table = 'daily_reward_claims';
    protected $fillable = ['user_id', 'day_number', 'claim_date'];
    protected $casts = ['claim_date' => 'date'];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}