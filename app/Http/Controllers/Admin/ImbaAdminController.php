<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Imba;
use Illuminate\Http\Request;

class ImbaAdminController extends Controller
{
    public function index()
    {
        $imbas = Imba::orderBy('code')->paginate(50);
        return view('admin.imbas', compact('imbas'));
    }

    public function edit($id)
    {
        $imba = Imba::findOrFail($id);
        return view('admin.imbas_edit', compact('imba'));
    }

    public function update(Request $request, $id)
    {
        $imba = Imba::findOrFail($id);
        $data = $request->validate([
            'name' => 'required|string',
            'base_price_coins' => 'nullable|integer|min:0',
            'base_price_gems' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);
        $imba->update($data);
        return redirect()->route('admin.imbas.index')->with('success', "Имба {$imba->name} обновлена");
    }
}