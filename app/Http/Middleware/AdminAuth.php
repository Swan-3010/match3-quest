<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AdminAuth
{
    public function handle(Request $request, Closure $next)
    {
        // Простая проверка: email содержит "admin" или есть флаг is_admin
        // В продакшене — отдельный guard или роли (Spatie Permission)
        $user = $request->user();
        if (!$user || !$user->email_verified_at || !str_contains($user->email, 'admin')) {
            abort(403, 'Доступ запрещён. Требуются права администратора.');
        }
        return $next($request);
    }
}
