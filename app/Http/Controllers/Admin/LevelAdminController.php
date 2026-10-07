<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Level;
use App\Models\LevelReward;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class LevelAdminController extends Controller
{
    public function index(Request $request)
    {
        $levels = Level::with('rewards')
            ->when($request->search, fn($q, $s) => $q->where('level_number', 'like', "%{$s}%"))
            ->orderBy('level_number')
            ->paginate(50);
        return view('admin.levels', compact('levels'));
    }

    public function edit($id)
    {
        $level = Level::with('rewards')->findOrFail($id);
        return view('admin.levels_edit', compact('level'));
    }

    public function update(Request $request, $id)
    {
        $level = Level::findOrFail($id);
        $data = $request->validate([
            'moves_limit' => 'required|integer|min:1',
            'target_score' => 'required|integer|min:1',
            'star_2_score' => 'required|integer|min:1',
            'star_3_score' => 'required|integer|min:1',
            'colors_count' => 'required|integer|between:3,8',
            'is_active' => 'boolean',
        ]);

        $level->update($data);
        Cache::forget("level.{$id}");

        return redirect()->route('admin.levels.index')->with('success', "Уровень {$level->level_number} обновлён");
    }

    public function import(Request $request)
    {
        $request->validate(['csv' => 'required|file|mimes:csv,txt']);
        $file = $request->file('csv');
        $handle = fopen($file->getRealPath(), 'r');
        $header = fgetcsv($handle);

        $count = 0;
        while ($row = fgetcsv($handle)) {
            $data = array_combine($header, $row);
            Level::updateOrCreate(
                ['level_number' => $data['level']],
                [
                    'target_score' => $data['target_score'],
                    'star_2_score' => $data['star_2_score'],
                    'star_3_score' => $data['star_3_score'],
                    'moves_limit' => $data['moves_limit'],
                    'colors_count' => $data['colors_count'],
                ]
            );
            $count++;
        }
        fclose($handle);
        Cache::flush();

        return redirect()->route('admin.levels.index')->with('success', "Импортировано {$count} уровней");
    }

    public function generate(Request $request)
    {
        $count = $request->input('count', 500);
        // Delegate to artisan command
        $exitCode = \Illuminate\Support\Facades\Artisan::call('levels:generate', ['count' => $count]);
        return redirect()->route('admin.levels.index')->with('success', "Сгенерировано {$count} уровней");
    }
}