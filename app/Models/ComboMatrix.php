<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ComboMatrix extends Model
{
    protected $table = 'combo_matrix';
    protected $fillable = ['special_a', 'special_b', 'resulting_effect_json', 'is_active'];
    protected $casts = ['resulting_effect_json' => 'array', 'is_active' => 'boolean'];
}