<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WalletTransaction extends Model
{
    protected $table = 'wallet_transactions';
    protected $fillable = [
        'user_id', 'currency', 'amount', 'balance_after',
        'type', 'ref_type', 'ref_id', 'meta', 'client_request_id'
    ];
    protected $casts = ['meta' => 'array'];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
