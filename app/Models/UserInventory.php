<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserInventory extends Model
{
    protected $table = 'user_inventories';
    protected $fillable = ['user_id', 'imba_code', 'quantity'];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}