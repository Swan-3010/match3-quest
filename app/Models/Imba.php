<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Imba extends Model
{
    protected $fillable = [
        'code', 'name', 'unlock_condition', 'effect_json',
        'is_purchasable', 'base_price_coins', 'base_price_gems', 'is_active'
    ];
    protected $casts = ['effect_json' => 'array', 'is_purchasable' => 'boolean', 'is_active' => 'boolean'];
}