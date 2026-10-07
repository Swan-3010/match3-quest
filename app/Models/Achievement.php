<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Achievement extends Model
{
    protected $fillable = [
        'code', 'name', 'description', 'condition_type', 'condition_value',
        'reward_type', 'reward_qty', 'reward_imba_code', 'icon', 'is_active'
    ];
    protected $casts = ['is_active' => 'boolean'];

    public function userAchievements(): HasMany { return $this->hasMany(UserAchievement::class); }
}