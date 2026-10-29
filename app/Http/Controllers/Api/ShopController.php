<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Purchase;
use App\Models\ShopItem;
use App\Models\UserProgress;
use App\Services\Game\EconomyConfig;
use App\Services\Game\WalletService;
use DomainException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ShopController extends Controller
{
    public function items(Request $request)
    {
        $context = $request->query('context', 'general');
        $category = $request->query('category');

        $items = Cache::remember("shop.{$context}.{$category}", 3600, function () use ($context, $category) {
            $q = ShopItem::where('is_active', true)
                ->where(function ($q) use ($context) {
                    $q->where('context', $context)->orWhere('context', 'general');
                });
            if ($category) $q->where('category', $category);
            return $q->orderBy('sort_order')->get();
        });

        return response()->json($items);
    }

    public function purchase(Request $request)
    {
        $data = $request->validate([
            'item_code' => 'required|string',
            'quantity' => 'required|integer|min:1|max:99',
            'currency' => 'nullable|string', // legacy, цена всегда из товара
            'context' => 'nullable|string',
            'client_request_id' => 'nullable|string|max:64',
        ]);

        $user = $request->user();
        $clientRequestId = $data['client_request_id'] ?? null;

        if ($clientRequestId && Purchase::where('user_id', $user->id)->where('client_request_id', $clientRequestId)->exists()) {
            return response()->json($this->resultPayload($user, $clientRequestId));
        }

        $item = ShopItem::where('code', $data['item_code'])->where('is_active', true)->firstOrFail();

        $currency = $item->price_currency
            ?? ($item->price_coins === null && $item->price_gems !== null ? 'gems' : 'coins');
        $price = $currency === 'gems' ? $item->price_gems : $item->price_coins;
        if ($price === null) {
            return response()->json(['error' => 'Товар недоступен для покупки'], 422);
        }

        $quantity = (int) $data['quantity'];
        $wallet = app(WalletService::class);

        try {
            DB::transaction(function () use ($user, $item, $currency, $price, $quantity, $data, $clientRequestId, $wallet) {
                $progress = UserProgress::where('user_id', $user->id)->lockForUpdate()->firstOrFail();

                if ($item->category === 'life') {
                    $maxLives = (int) EconomyConfig::get('max_lives', 5);
                    if ($progress->lives >= $maxLives) {
                        throw new DomainException('lives_full');
                    }
                }

                $wallet->apply($progress, $currency, -$price * $quantity, 'purchase', 'shop_item', $item->id, [
                    'code' => $item->code,
                    'quantity' => $quantity,
                ]);

                switch ($item->category) {
                    case 'imba':
                    case 'boost':
                        $wallet->apply($progress, 'booster:'.$item->code, $quantity, 'purchase_item', 'shop_item', $item->id);
                        break;
                    case 'life':
                        $maxLives = (int) EconomyConfig::get('max_lives', 5);
                        $qty = (int) ($item->effect_json['qty'] ?? 1);
                        $amount = min($qty, $maxLives - $progress->lives);
                        $wallet->apply($progress, 'lives', $amount, 'purchase_item', 'shop_item', $item->id);
                        break;
                    case 'gems':
                        $wallet->apply($progress, 'gems', $quantity, 'purchase_item', 'shop_item', $item->id);
                        break;
                }

                Purchase::create([
                    'user_id' => $user->id,
                    'shop_item_code' => $item->code,
                    'quantity' => $quantity,
                    'price_paid' => $price * $quantity,
                    'currency' => $currency,
                    'context' => $data['context'] ?? 'general',
                    'client_request_id' => $clientRequestId,
                ]);
            });
        } catch (DomainException $e) {
            $status = $e->getMessage() === 'lives_full' ? 422 : 402;
            return response()->json(['error' => $e->getMessage()], $status);
        }

        return response()->json($this->resultPayload($user, $clientRequestId));
    }

    private function resultPayload($user, ?string $clientRequestId): array
    {
        $progress = $user->progress()->firstOrFail();
        return [
            'success' => true,
            'inventory' => $user->inventory()->get(),
            'balance' => ['coins' => $progress->coins, 'gems' => $progress->gems, 'lives' => $progress->lives],
            'client_request_id' => $clientRequestId,
        ];
    }
}
