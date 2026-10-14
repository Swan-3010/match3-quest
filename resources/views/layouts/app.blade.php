<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="api-base" content="{{ url('/api/v1') }}">
<meta name="app-base" content="{{ url('/') }}/">
<title>@yield('title', 'Match Quest')</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
  :root {
    --bg: #0a0a0f; --surface: #12121a; --surface-2: #1a1a24; --surface-3: #222230;
    --border: #2a2a3a; --text: #f0f0f5; --text-2: #a0a0b8; --text-3: #606080;
    --accent: #6366f1; --accent-2: #8b5cf6; --gold: #f59e0b; --danger: #ef4444;
    --success: #22c55e; --radius-sm: 6px; --radius-md: 10px; --radius-lg: 14px;
  }
  * { box-sizing: border-box; margin: 0; padding: 0; -webkit-tap-highlight-color: transparent; }
  body {
    font-family: 'Inter', system-ui, sans-serif;
    background: var(--bg); color: var(--text);
    min-height: 100vh; overflow-x: hidden;
  }
  #app { max-width: 420px; margin: 0 auto; min-height: 100vh; position: relative; }
  .screen { display: none; animation: fadeIn 0.25s ease-out; }
  .screen.active { display: block; }
  @keyframes fadeIn { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }
  .btn {
    display: flex; align-items: center; justify-content: center; gap: 8px;
    padding: 14px 20px; border-radius: var(--radius-md); border: none;
    font-size: 15px; font-weight: 500; cursor: pointer; transition: all 0.15s;
    font-family: inherit;
  }
  .btn-primary { background: linear-gradient(135deg, var(--accent), var(--accent-2)); color: white; }
  .btn-secondary { background: var(--surface-2); color: var(--text); border: 1px solid var(--border); }
  .btn-gold { background: linear-gradient(135deg, #d97706, var(--gold)); color: white; }
  .btn-danger { background: linear-gradient(135deg, #dc2626, var(--danger)); color: white; }
  .btn:active { transform: scale(0.96); }
  .input-field {
    background: var(--surface-2); border: 1px solid var(--border); border-radius: var(--radius-md);
    padding: 12px 14px; color: var(--text); font-size: 15px; font-family: inherit; outline: none; width: 100%;
  }
  .input-field:focus { border-color: var(--accent); }
  .currency-bar {
    display: flex; align-items: center; gap: 8px;
  }
  .currency-item {
    display: flex; align-items: center; gap: 4px;
    font-size: 13px; font-weight: 600; background: var(--surface-2);
    padding: 4px 10px; border-radius: 999px; border: 1px solid var(--border);
  }
  .top-bar {
    display: flex; align-items: center; justify-content: space-between;
    padding: 10px 14px; background: var(--surface); border-bottom: 1px solid var(--border);
  }
  .bottom-nav {
    display: flex; justify-content: space-around;
    padding: 8px 0 12px; background: var(--surface);
    border-top: 1px solid var(--border); position: fixed; bottom: 0;
    max-width: 420px; width: 100%; z-index: 100;
  }
  .nav-item {
    display: flex; flex-direction: column; align-items: center; gap: 2px;
    background: none; border: none; color: var(--text-3);
    font-size: 10px; font-family: inherit; cursor: pointer; padding: 4px 10px;
  }
  .nav-item.active { color: var(--text); }
  .nav-item .icon { font-size: 20px; }
  .loading-overlay {
    position: fixed; inset: 0; background: rgba(0,0,0,0.8);
    display: none; align-items: center; justify-content: center; z-index: 1000;
  }
  .loading-overlay.active { display: flex; }
  .spinner {
    width: 40px; height: 40px; border: 3px solid var(--surface-2);
    border-top-color: var(--accent); border-radius: 50%;
    animation: spin 0.8s linear infinite;
  }
  @keyframes spin { to { transform: rotate(360deg); } }
  .toast {
    position: fixed; top: 20px; left: 50%; transform: translateX(-50%) translateY(-100px);
    background: var(--surface); border: 1px solid var(--border); padding: 12px 20px;
    border-radius: var(--radius-md); z-index: 1001; transition: transform 0.3s;
    font-size: 14px;
  }
  .toast.show { transform: translateX(-50%) translateY(0); }
</style>
@stack('styles')

<link rel="manifest" href="{{ asset('manifest.json') }}">
<link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
<meta name="theme-color" content="#6366f1">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<link rel="apple-touch-icon" href="{{ asset('assets/icons/icon-192x192.png') }}">
<script>
  if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register(
      new URL('sw.js', document.querySelector('meta[name="app-base"]').content).href,
      { scope: document.querySelector('meta[name="app-base"]').content }
    )
      .then(reg => console.log('SW registered:', reg.scope))
      .catch(err => console.log('SW failed:', err));
  }
</script>

</head>
<body>
<div id="app">
  @yield('content')
</div>

<div class="loading-overlay" id="loadingOverlay"><div class="spinner"></div></div>
<div class="toast" id="toast"></div>

<script>
window.API_BASE = document.querySelector('meta[name="api-base"]').content;
window.CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]').content;
</script>
<script src="{{ asset('assets/js/auth.js?v=20261013a') }}"></script>
<script src="{{ asset('assets/js/api-client.js?v=20261013a') }}"></script>
<script type="module" src="{{ asset('assets/js/app.js?v=20261014a') }}"></script>
@stack('scripts')
</body>
</html>