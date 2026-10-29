<?php
namespace App\Services\Game;

use App\Models\UserInventory;
use App\Models\UserProgress;
use App\Models\WalletTransaction;
use DomainException;
use Illuminate\Support\Facades\DB;

class WalletService
{
    public function apply(UserProgress $progress, string $currency, int $amount, string $type, ?string $refType = null, ?int $refId = null, array $meta = []): WalletTransaction
    {
        return DB::transaction(function () use ($progress, $currency, $amount, $type, $refType, $refId, $meta) {
            $locked = UserProgress::where('id', $progress->id)->lockForUpdate()->firstOrFail();

            if (str_starts_with($currency, 'booster:')) {
                $code = substr($currency, strlen('booster:'));
                $inv = UserInventory::firstOrCreate(
                    ['user_id' => $locked->user_id, 'imba_code' => $code],
                    ['quantity' => 0]
                );
                if ($amount < 0 && $inv->quantity < abs($amount)) {
                    throw new DomainException('insufficient_funds');
                }
                $inv->quantity += $amount;
                $inv->save();

                return WalletTransaction::create([
                    'user_id' => $locked->user_id,
                    'currency' => $currency,
                    'amount' => $amount,
                    'balance_after' => $inv->quantity,
                    'type' => $type,
                    'ref_type' => $refType,
                    'ref_id' => $refId,
                    'meta' => $meta ?: null,
                ]);
            }

            if ($amount < 0 && $locked->{$currency} < abs($amount)) {
                throw new DomainException('insufficient_funds');
            }
            $locked->{$currency} += $amount;
            $locked->save();

            return WalletTransaction::create([
                'user_id' => $locked->user_id,
                'currency' => $currency,
                'amount' => $amount,
                'balance_after' => $locked->{$currency},
                'type' => $type,
                'ref_type' => $refType,
                'ref_id' => $refId,
                'meta' => $meta ?: null,
            ]);
        });
    }
}
