<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ShopItem;
use App\Models\Purchase;
use App\Models\UserInventory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

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
            'quantity' => 'required|integer|min:1',
            'currency' => 'required|in:coins,gems',
            'context' => 'nullable|string',
        ]);

        $item = ShopItem::where('code', $data['item_code'])->where('is_active', true)->first();
        if (!$item) return response()->json(['error' => 'Товар не найден'], 404);

        $user = $request->user();
        $progress = $user->progress;

        $price = $data['currency'] === 'coins' ? $item->price_coins : $item->price_gems;
        $balanceField = $data['currency'] === 'coins' ? 'coins' : 'gems';

        if (!$price || $progress->{$balanceField} < $price * $data['quantity']) {
            return response()->json(['error' => 'Недостаточно средств'], 402);
        }

        // Deduct
        $progress->{$balanceField} -= $price * $data['quantity'];
        $progress->save();

        // Add to inventory if imba
        if ($item->category === 'imba') {
            $inv = UserInventory::firstOrCreate(
                ['user_id' => $user->id, 'imba_code' => $item->code],
                ['quantity' => 0]
            );
            $inv->quantity += $data['quantity'];
            $inv->save();
        }

        Purchase::create([
            'user_id' => $user->id,
            'shop_item_code' => $item->code,
            'quantity' => $data['quantity'],
            'price_paid' => $price * $data['quantity'],
            'currency' => $data['currency'],
            'context' => $data['context'] ?? 'general',
        ]);

        return response()->json([
            'success' => true,
            'inventory' => $user->inventory()->get(),
            'balance' => ['coins' => $progress->coins, 'gems' => $progress->gems, 'lives' => $progress->lives],
        ]);
    }
}