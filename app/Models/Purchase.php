<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Purchase extends Model
{
    protected $fillable = ['user_id', 'shop_item_code', 'quantity', 'price_paid', 'currency', 'context', 'client_request_id'];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}