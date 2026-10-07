<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Feature extends Model
{
    protected $fillable = ['unlock_level', 'code', 'name', 'unlock_condition', 'effect_json', 'board_impact', 'is_active'];
    protected $casts = ['effect_json' => 'array', 'is_active' => 'boolean'];
}