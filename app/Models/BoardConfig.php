<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BoardConfig extends Model
{
    protected $table = 'board_configs';
    protected $fillable = ['width', 'height', 'spawn_weights', 'obstacle_density', 'notes'];
    protected $casts = ['spawn_weights' => 'array', 'obstacle_density' => 'array'];
}