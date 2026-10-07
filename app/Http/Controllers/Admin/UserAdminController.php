<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserAdminController extends Controller
{
    public function index(Request $request)
    {
        $users = User::with('progress')
            ->when($request->search, fn($q, $s) => $q->where('email', 'like', "%{$s}%"))
            ->orderByDesc('created_at')
            ->paginate(50);
        return view('admin.users', compact('users'));
    }

    public function show($id)
    {
        $user = User::with(['progress', 'inventory', 'achievements.achievement', 'sessions'])->findOrFail($id);
        return view('admin.users_show', compact('user'));
    }
}