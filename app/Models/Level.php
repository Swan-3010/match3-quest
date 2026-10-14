<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Level extends Model
{
    use HasFactory;

    protected $fillable = [
        'level_number', 'moves_limit', 'target_score', 'star_2_score', 'star_3_score',
        'obstacles_config', 'colors_count', 'special_unlocked', 'config_version', 'is_active'
    ];
    protected $casts = [
        'obstacles_config' => 'array',
        'is_active' => 'boolean',
    ];

    public function rewards(): HasMany { return $this->hasMany(LevelReward::class); }
}