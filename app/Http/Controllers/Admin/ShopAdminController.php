<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ShopItem;
use Illuminate\Http\Request;

class ShopAdminController extends Controller
{
    public function index()
    {
        $items = ShopItem::orderBy('sort_order')->paginate(50);
        return view('admin.shop', compact('items'));
    }

    public function create()
    {
        return view('admin.shop_create');
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        ShopItem::create($data);
        return redirect()->route('admin.shop.index')->with('success', "Товар {$data['name']} создан");
    }

    public function edit($id)
    {
        $item = ShopItem::findOrFail($id);
        return view('admin.shop_edit', compact('item'));
    }

    public function update(Request $request, $id)
    {
        $item = ShopItem::findOrFail($id);
        $data = $this->validated($request, $item->id);
        $item->update($data);
        return redirect()->route('admin.shop.index')->with('success', "Товар {$item->name} обновлён");
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        $data = $request->validate([
            'code' => 'required|string|unique:shop_items,code'.($ignoreId ? ",{$ignoreId}" : ''),
            'name' => 'required|string',
            'category' => 'required|in:imba,boost,life,gems',
            'price_coins' => 'nullable|integer|min:0',
            'price_gems' => 'nullable|integer|min:0',
            'price_currency' => 'nullable|in:coins,gems',
            'context' => 'nullable|string',
            'effect_json' => 'nullable|string',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer',
        ]);
        $data['effect_json'] = isset($data['effect_json']) && $data['effect_json'] !== ''
            ? json_decode($data['effect_json'], true)
            : null;
        $data['sort_order'] = $data['sort_order'] ?? 0;
        return $data;
    }
}