<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Feature;
use Illuminate\Http\Request;

class FeatureAdminController extends Controller
{
    public function index()
    {
        $features = Feature::orderBy('unlock_level')->paginate(50);
        return view('admin.features', compact('features'));
    }

    public function edit($id)
    {
        $feature = Feature::findOrFail($id);
        return view('admin.features_edit', compact('feature'));
    }

    public function update(Request $request, $id)
    {
        $feature = Feature::findOrFail($id);
        $data = $request->validate([
            'unlock_level' => 'required|integer|min:1',
            'name' => 'required|string',
            'unlock_condition' => 'required|string',
            'is_active' => 'boolean',
        ]);
        $feature->update($data);
        return redirect()->route('admin.features.index')->with('success', "Фишка {$feature->name} обновлена");
    }
}