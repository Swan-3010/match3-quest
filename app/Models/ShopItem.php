<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShopItem extends Model
{
    protected $table = 'shop_items';
    protected $fillable = [
        'code', 'name', 'category', 'price_coins', 'price_gems',
        'context', 'effect_json', 'is_active', 'sort_order'
    ];
    protected $casts = ['effect_json' => 'array', 'is_active' => 'boolean'];
}