<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>@yield('title', 'Админка') — Match Quest</title>
<style>
  :root {
    --bg: #0f172a; --surface: #1e293b; --surface-2: #334155; --border: #475569;
    --text: #f1f5f9; --text-2: #94a3b8; --text-3: #64748b;
    --accent: #6366f1; --accent-2: #8b5cf6; --gold: #f59e0b;
    --danger: #ef4444; --success: #22c55e; --warning: #f97316;
    --radius: 8px;
  }
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: 'Inter', system-ui, sans-serif; background: var(--bg); color: var(--text); }
  .admin-wrapper { display: flex; min-height: 100vh; }
  .sidebar { width: 240px; background: var(--surface); border-right: 1px solid var(--border); padding: 20px 0; position: fixed; height: 100vh; overflow-y: auto; }
  .sidebar .logo { padding: 0 20px 20px; font-size: 18px; font-weight: 700; color: var(--accent); border-bottom: 1px solid var(--border); margin-bottom: 16px; }
  .nav-group { margin-bottom: 16px; }
  .nav-group-title { padding: 8px 20px; font-size: 11px; text-transform: uppercase; color: var(--text-3); letter-spacing: 0.5px; }
  .nav-link { display: block; padding: 10px 20px; color: var(--text-2); text-decoration: none; font-size: 14px; transition: all 0.15s; }
  .nav-link:hover, .nav-link.active { background: var(--surface-2); color: var(--text); }
  .nav-link .icon { margin-right: 8px; }
  .main { margin-left: 240px; flex: 1; padding: 24px 32px; }
  .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
  .page-header h1 { font-size: 24px; font-weight: 700; }
  .btn { padding: 8px 16px; border-radius: var(--radius); border: none; font-size: 14px; font-weight: 500; cursor: pointer; font-family: inherit; transition: all 0.15s; }
  .btn-primary { background: var(--accent); color: white; }
  .btn-secondary { background: var(--surface-2); color: var(--text); border: 1px solid var(--border); }
  .btn-danger { background: var(--danger); color: white; }
  .btn-sm { padding: 6px 12px; font-size: 12px; }
  .card { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); padding: 20px; margin-bottom: 20px; }
  .card-title { font-size: 16px; font-weight: 600; margin-bottom: 16px; }
  .stats-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px; }
  .stat-card { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); padding: 16px; }
  .stat-card .value { font-size: 28px; font-weight: 700; color: var(--accent); }
  .stat-card .label { font-size: 12px; color: var(--text-3); margin-top: 4px; }
  .table { width: 100%; border-collapse: collapse; }
  .table th { text-align: left; padding: 10px 12px; font-size: 12px; text-transform: uppercase; color: var(--text-3); border-bottom: 1px solid var(--border); font-weight: 500; }
  .table td { padding: 12px; border-bottom: 1px solid var(--border); font-size: 14px; }
  .table tr:hover { background: var(--surface-2); }
  .badge { display: inline-block; padding: 2px 8px; border-radius: 999px; font-size: 11px; font-weight: 600; }
  .badge-success { background: rgba(34,197,94,0.15); color: var(--success); }
  .badge-danger { background: rgba(239,68,68,0.15); color: var(--danger); }
  .badge-warning { background: rgba(249,115,22,0.15); color: var(--warning); }
  .form-group { margin-bottom: 16px; }
  .form-label { display: block; font-size: 13px; font-weight: 500; margin-bottom: 6px; color: var(--text-2); }
  .form-input { width: 100%; padding: 10px 12px; background: var(--surface-2); border: 1px solid var(--border); border-radius: var(--radius); color: var(--text); font-size: 14px; font-family: inherit; }
  .form-input:focus { outline: none; border-color: var(--accent); }
  .alert { padding: 12px 16px; border-radius: var(--radius); margin-bottom: 16px; }
  .alert-success { background: rgba(34,197,94,0.1); border: 1px solid var(--success); color: var(--success); }
  .search-box { display: flex; gap: 8px; margin-bottom: 16px; }
  .search-box input { flex: 1; }
  .pagination { display: flex; gap: 4px; margin-top: 16px; }
  .pagination a, .pagination span { padding: 6px 12px; background: var(--surface-2); border: 1px solid var(--border); border-radius: var(--radius); color: var(--text-2); text-decoration: none; font-size: 13px; }
  .pagination a:hover { background: var(--accent); color: white; }
  .pagination .active { background: var(--accent); color: white; }
  .chart-bar { display: flex; align-items: flex-end; gap: 4px; height: 120px; padding: 10px 0; }
  .chart-bar-item { flex: 1; background: var(--accent); border-radius: 4px 4px 0 0; min-height: 4px; opacity: 0.8; transition: opacity 0.15s; }
  .chart-bar-item:hover { opacity: 1; }
  .chart-labels { display: flex; gap: 4px; }
  .chart-labels span { flex: 1; text-align: center; font-size: 10px; color: var(--text-3); }
</style>
</head>
<body>
<div class="admin-wrapper">
  <aside class="sidebar">
    <div class="logo">💎 Match Quest</div>
    <div class="nav-group">
      <div class="nav-group-title">Главное</div>
      <a href="{{ route('admin.dashboard') }}" class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}"><span class="icon">📊</span>Дашборд</a>
    </div>
    <div class="nav-group">
      <div class="nav-group-title">Контент</div>
      <a href="{{ route('admin.levels.index') }}" class="nav-link {{ request()->routeIs('admin.levels.*') ? 'active' : '' }}"><span class="icon">🎮</span>Уровни</a>
      <a href="{{ route('admin.features.index') }}" class="nav-link {{ request()->routeIs('admin.features.*') ? 'active' : '' }}"><span class="icon">⭐</span>Фишки</a>
      <a href="{{ route('admin.imbas.index') }}" class="nav-link {{ request()->routeIs('admin.imbas.*') ? 'active' : '' }}"><span class="icon">🔨</span>Имбы</a>
      <a href="{{ route('admin.shop.index') }}" class="nav-link {{ request()->routeIs('admin.shop.*') ? 'active' : '' }}"><span class="icon">🛒</span>Магазин</a>
    </div>
    <div class="nav-group">
      <div class="nav-group-title">Пользователи</div>
      <a href="{{ route('admin.users.index') }}" class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}"><span class="icon">👤</span>Игроки</a>
      <a href="{{ route('admin.analytics.index') }}" class="nav-link {{ request()->routeIs('admin.analytics.*') ? 'active' : '' }}"><span class="icon">📈</span>Аналитика</a>
    </div>
    <div class="nav-group">
      <a href="/" class="nav-link"><span class="icon">🏠</span>В игру</a>
    </div>
  </aside>
  <main class="main">
    @if(session('success'))
      <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @yield('content')
  </main>
</div>
</body>
</html>