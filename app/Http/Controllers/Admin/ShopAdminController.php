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

    public function edit($id)
    {
        $item = ShopItem::findOrFail($id);
        return view('admin.shop_edit', compact('item'));
    }

    public function update(Request $request, $id)
    {
        $item = ShopItem::findOrFail($id);
        $data = $request->validate([
            'name' => 'required|string',
            'price_coins' => 'nullable|integer|min:0',
            'price_gems' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
            'sort_order' => 'required|integer',
        ]);
        $item->update($data);
        return redirect()->route('admin.shop.index')->with('success', "Товар {$item->name} обновлён");
    }
}