<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameConfig extends Model
{
    protected $table = 'game_configs';
    protected $fillable = ['config_key', 'config_value', 'version'];
    protected $casts = ['config_value' => 'array'];
}