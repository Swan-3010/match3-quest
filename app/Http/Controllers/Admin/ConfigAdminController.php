<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GameConfig;
use Illuminate\Http\Request;

class ConfigAdminController extends Controller
{
    public function index()
    {
        $configs = GameConfig::orderBy('config_key')->paginate(50);
        return view('admin.configs', compact('configs'));
    }

    public function edit($id)
    {
        $config = GameConfig::findOrFail($id);
        return view('admin.configs_edit', compact('config'));
    }

    public function update(Request $request, $id)
    {
        $config = GameConfig::findOrFail($id);
        $data = $request->validate([
            'config_value' => 'required|string',
        ]);
        $value = json_decode($data['config_value'], true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return back()->withErrors(['config_value' => 'Невалидный JSON'])->withInput();
        }
        $config->update([
            'config_value' => $value,
            'version' => $config->version + 1,
        ]);
        return redirect()->route('admin.configs.index')->with('success', "Конфиг {$config->config_key} обновлён");
    }
}
