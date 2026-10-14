@extends('layouts.app')

@section('title', 'Match Quest')

@push('styles')
<style>
  /* ===== SCREENS ===== */
  .screen { display: none; padding: 0; min-height: 100vh; padding-bottom: 60px; }
  .screen.active { display: block; }

  /* AUTH */
  .auth-screen.active { display: flex; flex-direction: column; justify-content: center; gap: 16px; padding: 40px 24px; min-height: 100vh; }
  .auth-logo { text-align: center; margin-bottom: 20px; }
  .auth-logo .icon { font-size: 48px; margin-bottom: 12px; display: block; }
  .auth-logo h2 { font-size: 22px; font-weight: 700; }
  .auth-logo p { color: var(--text-2); font-size: 13px; margin-top: 4px; }
  .input-group { display: flex; flex-direction: column; gap: 6px; }
  .input-group label { font-size: 13px; color: var(--text-2); font-weight: 500; }
  .auth-links { display: flex; justify-content: space-between; font-size: 13px; }
  .remember-row { display: flex; align-items: center; gap: 8px; font-size: 13px; color: var(--text-2); cursor: pointer; }
  .remember-row input { width: 16px; height: 16px; accent-color: var(--accent); }
  .auth-links a { color: var(--accent); text-decoration: none; }
  .auth-divider { display: flex; align-items: center; gap: 12px; color: var(--text-3); font-size: 12px; margin: 8px 0; }
  .auth-divider::before, .auth-divider::after { content: ''; flex: 1; height: 1px; background: var(--border); }

  /* MENU */
  .menu-header { text-align: center; padding: 32px 20px 24px; background: linear-gradient(180deg, rgba(99,102,241,0.08) 0%, transparent 100%); }
  .menu-header .logo-icon { font-size: 56px; margin-bottom: 8px; display: inline-block; animation: float 3s ease-in-out infinite; }
  @keyframes float { 0%,100%{transform:translateY(0)} 50%{transform:translateY(-6px)} }
  .menu-header h2 { font-size: 26px; font-weight: 700; background: linear-gradient(135deg, var(--accent), var(--accent-2)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
  .menu-header p { color: var(--text-2); font-size: 13px; margin-top: 4px; }
  .menu-stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; padding: 0 16px; margin-bottom: 16px; }
  .menu-stat { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-md); padding: 12px 8px; text-align: center; }
  .menu-stat .num { font-size: 18px; font-weight: 700; }
  .menu-stat .lbl { font-size: 11px; color: var(--text-3); margin-top: 2px; }
  .menu-grid { display: flex; flex-direction: column; gap: 10px; padding: 0 16px 16px; }
  .menu-btn { padding: 16px; font-size: 16px; border-radius: var(--radius-md); }
  .menu-btn .icon { font-size: 22px; }
  .menu-btn-row { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
  .daily-btn { background: linear-gradient(135deg, #ec4899, #f472b6); color: white; position: relative; }
  .daily-badge { position: absolute; top: -4px; right: -4px; background: var(--danger); color: white; font-size: 10px; font-weight: 700; padding: 2px 6px; border-radius: 999px; border: 2px solid var(--bg); }

  /* MAP */
  .map-container { display: flex; flex-direction: column; align-items: center; gap: 12px; padding: 20px 0 100px; overflow-y: auto; max-height: calc(100vh - 60px); }
  .map-container::-webkit-scrollbar { display: none; }
  .level-node { width: 56px; height: 56px; border-radius: 50%; display: flex; flex-direction: column; align-items: center; justify-content: center; font-weight: 700; font-size: 16px; cursor: pointer; transition: all 0.2s; position: relative; border: 3px solid var(--border); flex-shrink: 0; }
  .level-node:active { transform: scale(0.92); }
  .level-node.completed { background: linear-gradient(135deg, #16a34a, var(--success)); border-color: var(--success); color: white; box-shadow: 0 4px 12px rgba(34,197,94,0.25); }
  .level-node.current { background: linear-gradient(135deg, var(--accent), var(--accent-2)); border-color: var(--accent); color: white; box-shadow: 0 0 24px rgba(99,102,241,0.4); animation: pulse-glow 2s infinite; width: 64px; height: 64px; font-size: 20px; }
  @keyframes pulse-glow { 0%,100%{box-shadow:0 0 20px rgba(99,102,241,0.3)} 50%{box-shadow:0 0 30px rgba(99,102,241,0.5)} }
  .level-node.locked { background: var(--surface-2); color: var(--text-3); cursor: not-allowed; font-size: 14px; }
  .path-line { width: 3px; height: 20px; background: var(--border); border-radius: 2px; flex-shrink: 0; }
  .path-line.done { background: var(--success); }

  /* GAME */
  .game-header { display: flex; justify-content: space-between; align-items: center; padding: 10px 14px; background: var(--surface); border-bottom: 1px solid var(--border); }
  .game-stat { text-align: center; background: var(--surface-2); padding: 6px 12px; border-radius: var(--radius-sm); border: 1px solid var(--border); min-width: 70px; }
  .game-stat .lbl { font-size: 9px; color: var(--text-3); text-transform: uppercase; letter-spacing: 0.8px; }
  .game-stat .val { font-size: 18px; font-weight: 700; font-variant-numeric: tabular-nums; }
  .game-boosters { display: flex; gap: 6px; padding: 6px 14px; background: var(--surface); border-bottom: 1px solid var(--border); overflow-x: auto; }
  .help-btn { position: absolute; top: 8px; right: 8px; z-index: 10; padding: 6px 13px; font-size: 14px; font-weight: 700; border-radius: 50%; }
  .help-overlay { position: fixed; inset: 0; background: rgba(0,0,0,.7); display: none; align-items: center; justify-content: center; z-index: 100; padding: 20px; }
  .help-overlay.open { display: flex; }
  .help-modal { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-lg, 16px); max-width: 420px; width: 100%; max-height: 80vh; overflow-y: auto; padding: 20px; font-size: 13px; line-height: 1.55; color: var(--text-2); }
  .help-modal h3 { color: var(--text-1, #fff); margin: 14px 0 6px; font-size: 15px; }
  .help-modal h3:first-child { margin-top: 0; }
  .help-modal ul { padding-left: 18px; margin: 4px 0; }
  .help-modal li { margin-bottom: 3px; }
  .help-modal table { width: 100%; border-collapse: collapse; margin: 6px 0; font-size: 12px; }
  .help-modal th, .help-modal td { border: 1px solid var(--border); padding: 4px 6px; text-align: left; }
  .help-close { float: right; background: none; border: none; color: var(--text-3); font-size: 20px; cursor: pointer; line-height: 1; padding: 0 2px; }
  .game-boosters::-webkit-scrollbar { display: none; }
  .booster-chip { display: flex; flex-direction: column; align-items: center; gap: 1px; background: var(--surface-2); border: 1px solid var(--border); border-radius: var(--radius-sm); padding: 4px 8px; min-width: 44px; font-size: 10px; flex-shrink: 0; cursor: pointer; }
  .booster-chip:active { transform: scale(0.92); }
  .booster-chip .icon { font-size: 18px; }
  .booster-chip .qty { font-size: 9px; color: var(--text-3); }
  .canvas-area { height: calc(100vh - 200px); display: flex; align-items: center; justify-content: center; padding: 8px; }
  #gameCanvasContainer { width: 100%; max-width: 360px; aspect-ratio: 1; }

  /* RESULT OVERLAY */
  .result-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.85); backdrop-filter: blur(8px); z-index: 1000; display: none; align-items: center; justify-content: center; padding: 20px; }
  .result-overlay.active { display: flex; }
  .result-card { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 32px 24px; text-align: center; width: 100%; max-width: 320px; animation: slideUp 0.4s ease-out; }
  @keyframes slideUp { from { transform: translateY(100%); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
  .result-card .icon { font-size: 56px; margin-bottom: 12px; }
  .result-card h3 { font-size: 24px; font-weight: 700; margin-bottom: 4px; }
  .result-stars { font-size: 32px; margin: 12px 0; letter-spacing: 4px; }
  .result-score { font-size: 18px; color: var(--gold); margin-bottom: 16px; }
  .result-rewards { font-size: 13px; color: var(--text-2); margin-bottom: 16px; }
  .result-actions { display: flex; flex-direction: column; gap: 8px; }

  /* SHOP */
  .shop-tabs { display: flex; gap: 4px; margin: 16px; padding: 4px; background: var(--surface); border-radius: var(--radius-md); border: 1px solid var(--border); }
  .shop-tab { flex: 1; padding: 8px; border: none; background: none; color: var(--text-2); font-size: 13px; font-weight: 500; border-radius: var(--radius-sm); cursor: pointer; font-family: inherit; }
  .shop-tab.active { background: var(--surface-2); color: var(--text); }
  .shop-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; padding: 0 16px 80px; }
  .shop-card { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-md); padding: 14px 10px; text-align: center; cursor: pointer; transition: all 0.15s; }
  .shop-card:active { transform: scale(0.96); }
  .shop-card .item-icon { font-size: 32px; margin-bottom: 6px; }
  .shop-card .item-name { font-size: 12px; font-weight: 600; margin-bottom: 2px; }
  .shop-card .item-desc { font-size: 10px; color: var(--text-3); margin-bottom: 8px; min-height: 28px; }
  .shop-card .price { display: inline-flex; align-items: center; gap: 4px; background: var(--surface-2); padding: 4px 10px; border-radius: 999px; font-size: 12px; font-weight: 600; border: 1px solid var(--border); }
  .shop-card .price.gold { color: var(--gold); }
  .shop-card .price.gem { color: var(--accent-2); }

  /* PROFILE */
  .profile-header { text-align: center; padding: 24px 20px 16px; background: linear-gradient(180deg, rgba(99,102,241,0.06) 0%, transparent 100%); }
  .avatar { width: 72px; height: 72px; border-radius: 50%; background: linear-gradient(135deg, var(--accent), var(--accent-2)); display: flex; align-items: center; justify-content: center; font-size: 32px; margin: 0 auto 10px; border: 3px solid var(--border); }
  .profile-name { font-size: 18px; font-weight: 700; }
  .profile-level { font-size: 13px; color: var(--text-2); margin-top: 2px; }
  .stats-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; padding: 0 16px; margin-bottom: 16px; }
  .stat-box { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-md); padding: 12px 6px; text-align: center; }
  .stat-box .num { font-size: 18px; font-weight: 700; }
  .stat-box .lbl { font-size: 10px; color: var(--text-3); margin-top: 2px; }
  .logout-btn { margin: 8px 16px 0; width: calc(100% - 32px); }

  /* DAILY */
  .daily-screen { padding: 24px 20px; text-align: center; }
  .daily-calendar { display: grid; grid-template-columns: repeat(7, 1fr); gap: 6px; margin: 20px 0; }
  .day-box { aspect-ratio: 1; background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-md); display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 2px; font-size: 11px; }
  .day-box.claimed { border-color: var(--success); background: rgba(34,197,94,0.08); }
  .day-box.today { border-color: var(--accent); box-shadow: 0 0 12px rgba(99,102,241,0.2); }
  .day-box .day-num { font-size: 10px; color: var(--text-3); }
  .day-box .day-icon { font-size: 18px; }
</style>
@endpush

@section('content')

<!-- TOP BAR (shared) -->
<div class="top-bar" id="sharedTopBar" style="display:none;">
  <button class="btn btn-secondary" style="padding:6px 10px;font-size:13px;" onclick="history.back()">← Назад</button>
  <div class="currency-bar">
    <div class="currency-item">❤️ <span id="currLives">5/5</span></div>
    <div class="currency-item">🪙 <span id="currCoins">0</span></div>
    <div class="currency-item">💎 <span id="currGems">0</span></div>
  </div>
</div>

<!-- SCREEN: AUTH -->
<div class="screen auth-screen active" id="screen-auth">
  <div class="auth-logo">
    <span class="icon">💎</span>
    <h2>Match Quest</h2>
    <p>Собери 3 в ряд — спаси королевство</p>
  </div>
  <div class="input-group">
    <label>Email</label>
    <input type="email" class="input-field" id="authEmail" placeholder="player@example.com">
  </div>
  <div class="input-group">
    <label>Пароль</label>
    <input type="password" class="input-field" id="authPassword" placeholder="••••••••">
  </div>
  <div class="input-group" id="confirmGroup" style="display:none;">
    <label>Повторите пароль</label>
    <input type="password" class="input-field" id="authPasswordConfirm" placeholder="••••••••">
  </div>
  <div class="input-group" id="secretGroup" style="display:none;">
    <label>Секретный вопрос (для восстановления пароля)</label>
    <input class="input-field" id="authSecretQuestion" placeholder="Например: кличка первого питомца">
  </div>
  <div class="input-group" id="secretAnswerGroup" style="display:none;">
    <label>Ответ на секретный вопрос</label>
    <input class="input-field" id="authSecretAnswer" placeholder="Запомнится только его хэш">
  </div>
  <div id="recoverPanel" style="display:none;">
    <div class="input-group">
      <label>Email аккаунта</label>
      <input type="email" class="input-field" id="recEmail" placeholder="player@example.com">
    </div>
    <div id="recStep1">
      <button class="btn btn-secondary" style="width:100%; margin-top:8px;" onclick="handleRecoverQuestion()">Показать секретный вопрос</button>
    </div>
    <div id="recStep2" style="display:none;">
      <div class="input-group"><label id="recQuestionLabel">Вопрос</label><input class="input-field" id="recAnswer"></div>
      <div class="input-group"><label>Новый пароль</label><input type="password" class="input-field" id="recPassword" placeholder="Минимум 8 символов"></div>
      <div class="input-group"><label>Повторите новый пароль</label><input type="password" class="input-field" id="recPasswordConfirm"></div>
      <button class="btn btn-primary" style="width:100%; margin-top:8px;" onclick="handleRecoverReset()">Сменить пароль</button>
    </div>
    <div class="auth-links" style="margin-top:12px;">
      <a href="#" onclick="toggleRecover(false); return false;">← Назад ко входу</a>
    </div>
  </div>
  <label class="remember-row" id="rememberRow"><input type="checkbox" id="authRemember" checked> Запомнить меня на 30 дней</label>
  <button class="btn btn-primary" id="authMainBtn" onclick="handleLogin()">Войти</button>
  <div class="auth-links">
    <a href="#" onclick="toggleRecover(true); return false;">Забыли пароль?</a>
    <a href="#" onclick="handleGuest()">Играть без регистрации</a>
  </div>
  <div class="auth-divider">или</div>
  <button class="btn btn-secondary" id="authToggleBtn" onclick="toggleAuthMode()">Создать аккаунт</button>
</div>

<!-- SCREEN: MENU -->
<div class="screen" id="screen-menu">
  <div class="menu-header">
    <span class="logo-icon">💎</span>
    <h2>Match Quest</h2>
    <p id="menuSubtitle">Загрузка...</p>
  </div>
  <div class="menu-stats">
    <div class="menu-stat"><div class="num" id="menuLevels">0</div><div class="lbl">Уровней</div></div>
    <div class="menu-stat"><div class="num" id="menuScore">0</div><div class="lbl">Очков</div></div>
    <div class="menu-stat"><div class="num" id="menuStars">0</div><div class="lbl">Звёзд</div></div>
  </div>
  <div class="menu-grid">
    <button class="btn btn-primary menu-btn" onclick="app.showScreen('map')">
      <span class="icon">▶️</span> Играть
    </button>
    <div class="menu-btn-row">
      <button class="btn btn-secondary menu-btn daily-btn" onclick="app.showScreen('daily'); app.loadDaily();">
        <span class="icon">🎁</span> Бонус
        <span class="daily-badge">!</span>
      </button>
      <button class="btn btn-secondary menu-btn" onclick="app.showScreen('shop'); app.loadShop();">
        <span class="icon">🛒</span> Магазин
      </button>
    </div>
    <button class="btn btn-secondary menu-btn" onclick="app.showScreen('profile'); app.loadProfile();">
      <span class="icon">👤</span> Профиль
    </button>
    <div class="menu-btn-row">
      <button class="btn btn-secondary menu-btn" onclick="toggleHelp(true)">
        <span class="icon">❓</span> Помощь
      </button>
      <button class="btn btn-secondary menu-btn" id="installBtn" onclick="app.installPwa()">
        <span class="icon">📲</span> Установить
      </button>
    </div>
  </div>
</div>

<!-- SCREEN: MAP -->
<div class="screen" id="screen-map">
  <div class="map-container" id="mapContainer"></div>
</div>

<!-- SCREEN: GAME -->
<div class="screen" id="screen-game">
  <button class="btn btn-secondary" onclick="app.goBack()" style="position:absolute;top:8px;left:8px;z-index:10;padding:6px 12px;font-size:13px;">← Меню</button>
  <button class="btn btn-secondary help-btn" onclick="toggleHelp(true)" aria-label="Помощь">?</button>
  <div class="game-header">
    <div class="game-stat"><div class="lbl">Цель</div><div class="val" id="gameTarget">0</div></div>
    <div class="game-stat"><div class="lbl">Счёт</div><div class="val" id="gameScore" style="color:var(--gold)">0</div></div>
    <div class="game-stat"><div class="lbl">Ходы</div><div class="val" id="gameMoves" style="color:var(--danger)">0</div></div>
  </div>
  <div class="game-boosters">
    <div class="booster-chip" onclick="app.useBooster('hammer')"><span class="icon">🔨</span><span class="qty">×3</span></div>
    <div class="booster-chip" onclick="app.useBooster('row')"><span class="icon">➡️</span><span class="qty">×2</span></div>
    <div class="booster-chip" onclick="app.useBooster('col')"><span class="icon">⬇️</span><span class="qty">×2</span></div>
    <div class="booster-chip" onclick="app.gameEngine?.shuffle()"><span class="icon">🔀</span><span class="qty">Shuffle</span></div>
  </div>
  <div class="canvas-area">
    <div id="gameCanvasContainer"></div>
  </div>
  <div class="bottom-nav" style="display:flex;justify-content:space-around;padding:12px;background:rgba(20,20,30,0.95);border-top:1px solid rgba(255,255,255,0.1);">
    <button class="nav-item" onclick="app.goBack()" style="background:none;border:none;color:#a0a0b0;font-size:13px;cursor:pointer;display:flex;flex-direction:column;align-items:center;gap:4px;">
      <span style="font-size:20px;">🏠</span>
      <span>Меню</span>
    </button>
    <button class="nav-item" onclick="app.showScreen('profile')" style="background:none;border:none;color:#a0a0b0;font-size:13px;cursor:pointer;display:flex;flex-direction:column;align-items:center;gap:4px;">
      <span style="font-size:20px;">👤</span>
      <span>Профиль</span>
    </button>
  </div>
</div>

<!-- RESULT OVERLAY -->
<div class="result-overlay" id="resultOverlay">
  <div class="result-card">
    <div class="icon" id="resultIcon">🏆</div>
    <h3 id="resultTitle">Победа!</h3>
    <div class="result-stars" id="resultStars">⭐⭐⭐</div>
    <div class="result-score" id="resultScore">0</div>
    <div class="result-rewards" id="resultRewards"></div>
    <div class="result-actions">
      <button class="btn btn-primary" onclick="app.hideResult(); app.showScreen('map')">К карте</button>
      <button class="btn btn-secondary" onclick="app.hideResult(); app.startLevel(app.currentLevelId + 1)">Следующий</button>
    </div>
  </div>
</div>

<!-- SCREEN: SHOP -->
<div class="screen" id="screen-shop">
  <div class="shop-tabs">
    <button class="shop-tab active" onclick="app.loadShop('imba')">Имбы</button>
    <button class="shop-tab" onclick="app.loadShop('boost')">Бустеры</button>
    <button class="shop-tab" onclick="app.loadShop('life')">Жизни</button>
    <button class="shop-tab" onclick="app.loadShop('gems')">Гемы</button>
  </div>
  <div class="shop-grid" id="shopGrid"></div>
</div>

<!-- SCREEN: PROFILE -->
<div class="screen" id="screen-profile">
  <div class="profile-header">
    <div class="avatar">🧙‍♂️</div>
    <div class="profile-name" id="profName">Игрок</div>
    <div class="profile-level" id="profLevel">Уровень 1</div>
    <div class="profile-level" id="profXp">0 / 5000 XP</div>
  </div>
  <div class="stats-row">
    <div class="stat-box"><div class="num" id="profLevels">0</div><div class="lbl">Уровней</div></div>
    <div class="stat-box"><div class="num" id="profScore">0</div><div class="lbl">Очков</div></div>
    <div class="stat-box"><div class="num" id="profStars">0</div><div class="lbl">Звёзд</div></div>
  </div>
  <button class="btn btn-secondary logout-btn" onclick="window.auth.logout()">Выйти из аккаунта</button>
</div>

<!-- SCREEN: DAILY -->
<div class="screen daily-screen" id="screen-daily">
  <h2 style="font-size:22px;font-weight:700;margin-bottom:8px;">🎁 Ежедневный бонус</h2>
  <p style="color:var(--text-2);font-size:13px;margin-bottom:16px;">Серия: <span id="dailyStreak" style="color:var(--accent);font-weight:700;">0</span> дней 🔥</p>
  <div class="daily-calendar">
    <div class="day-box claimed"><span class="day-num">1</span><span class="day-icon">🪙</span></div>
    <div class="day-box claimed"><span class="day-num">2</span><span class="day-icon">🪙</span></div>
    <div class="day-box today"><span class="day-num">3</span><span class="day-icon">🔨</span></div>
    <div class="day-box"><span class="day-num">4</span><span class="day-icon">🪙</span></div>
    <div class="day-box"><span class="day-num">5</span><span class="day-icon">❤️</span></div>
    <div class="day-box"><span class="day-num">6</span><span class="day-icon">🪙</span></div>
    <div class="day-box"><span class="day-num">7</span><span class="day-icon">💎</span></div>
  </div>
  <button class="btn btn-primary" id="dailyClaimBtn" onclick="app.claimDaily()" style="width:100%;max-width:280px;margin:0 auto;display:block;">Забрать награду</button>
  <button class="btn btn-ghost" onclick="app.showScreen('menu')" style="margin-top:12px;">В меню</button>
</div>

<!-- BOTTOM NAV -->
<div class="bottom-nav" id="bottomNav" style="display:none;">
  <button class="nav-item" data-screen="menu" onclick="app.showScreen('menu')"><span class="icon">🏠</span><span>Меню</span></button>
  <button class="nav-item" data-screen="map" onclick="app.showScreen('map'); app.renderMap(app.userProgress?.max_level_reached || 1)"><span class="icon">🗺️</span><span>Карта</span></button>
  <button class="nav-item" data-screen="shop" onclick="app.showScreen('shop'); app.loadShop()"><span class="icon">🛒</span><span>Магазин</span></button>
  <button class="nav-item" data-screen="profile" onclick="app.showScreen('profile'); app.loadProfile()"><span class="icon">👤</span><span>Профиль</span></button>
</div>

<script>
// Inline handlers for auth screen
async function handleLogin() {
  const email = document.getElementById('authEmail').value;
  const password = document.getElementById('authPassword').value;
  const remember = document.getElementById('authRemember').checked;
  try {
    await window.auth.login(email, password, remember);
    window.app.init();
  } catch (e) {
    showToast(e.message);
  }
}

async function handleRegister() {
  const email = document.getElementById('authEmail').value;
  const password = document.getElementById('authPassword').value;
  const passwordConfirm = document.getElementById('authPasswordConfirm').value;
  const secretQuestion = document.getElementById('authSecretQuestion').value.trim();
  const secretAnswer = document.getElementById('authSecretAnswer').value.trim();
  try {
    await window.auth.register(email, password, passwordConfirm, secretQuestion, secretAnswer);
    window.app.init();
  } catch (e) {
    showToast(e.message);
  }
}

function toggleRecover(open) {
  document.getElementById('recoverPanel').style.display = open ? 'block' : 'none';
  document.getElementById('authMainBtn').style.display = open ? 'none' : 'block';
  document.getElementById('authToggleBtn').style.display = open ? 'none' : 'block';
  const rr = document.getElementById('rememberRow');
  if (rr) rr.style.display = 'none';
  if (!open) {
    document.getElementById('recStep1').style.display = 'block';
    document.getElementById('recStep2').style.display = 'none';
    if (rr && authMode === 'login') rr.style.display = 'flex';
  }
}

async function handleRecoverQuestion() {
  const email = document.getElementById('recEmail').value.trim();
  try {
    const data = await window.auth.recoverQuestion(email);
    document.getElementById('recQuestionLabel').textContent = data.question;
    document.getElementById('recStep1').style.display = 'none';
    document.getElementById('recStep2').style.display = 'block';
  } catch (e) {
    showToast(e.message);
  }
}

async function handleRecoverReset() {
  const email = document.getElementById('recEmail').value.trim();
  const answer = document.getElementById('recAnswer').value;
  const password = document.getElementById('recPassword').value;
  const passwordConfirm = document.getElementById('recPasswordConfirm').value;
  try {
    await window.auth.recoverReset(email, answer, password, passwordConfirm);
    showToast('Пароль изменён. Войдите с новым паролем.');
    toggleRecover(false);
  } catch (e) {
    showToast(e.message);
  }
}

function handleGuest() {
  window.auth.isGuest = true;
  window.app.showScreen('menu');
  document.getElementById('sharedTopBar').style.display = 'flex';
  document.getElementById('bottomNav').style.display = 'flex';
}

function showToast(msg) {
  const toast = document.getElementById('toast');
  toast.textContent = msg;
  toast.classList.add('show');
  setTimeout(() => toast.classList.remove('show'), 2500);
}

function toggleHelp(open) {
  document.getElementById('helpOverlay').classList.toggle('open', open);
}

// Show/hide shared UI based on screen
window.addEventListener('load', () => {
  const originalShowScreen = window.App.prototype.showScreen;
App.prototype.showScreen = function(name) {
  originalShowScreen.call(this, name);
  const showNav = ['menu', 'map', 'shop', 'profile', 'daily'].includes(name);
  const showTop = name !== 'auth' && name !== 'menu';
  document.getElementById('bottomNav').style.display = showNav ? 'flex' : 'none';
  document.getElementById('sharedTopBar').style.display = showTop ? 'flex' : 'none';

  if (name === 'map' && this.userProgress) {
    this.renderMap(this.userProgress.max_level_reached || 1);
  }
  if (name === 'menu' && this.userProgress) {
    document.getElementById('menuLevels').textContent = this.userProgress.max_level_reached || 0;
    document.getElementById('menuScore').textContent = (this.userProgress.total_score || 0).toLocaleString();
    document.getElementById('menuStars').textContent = this.userProgress.total_stars || 0;
    document.getElementById('menuSubtitle').textContent = `Уровень ${this.userProgress.player_level} · ${this.userProgress.current_xp || 0} XP`;
  }
};
});
</script>

<div class="help-overlay" id="helpOverlay" onclick="if (event.target === this) toggleHelp(false)">
  <div class="help-modal">
    <button class="help-close" onclick="toggleHelp(false)" aria-label="Закрыть">✕</button>
    <h3>Как играть</h3>
    <ul>
      <li>Поле 9×9. Свайпайте соседние фишки, чтобы собрать <b>3 и более</b> одинаковых в ряд.</li>
      <li>Каждый свайп тратит <b>1 ход</b>. Наберите целевой счёт до конца ходов.</li>
      <li>Линия не собралась — фишки вернутся на место.</li>
    </ul>
    <h3>Спецфишки (линия из 4–5)</h3>
    <table>
      <tr><th>Спецфишка</th><th>Как получить</th><th>Эффект</th></tr>
      <tr><td>🚀 Ракета</td><td>4 в ряд</td><td>Чистит строку или столбец</td></tr>
      <tr><td>💣 Бомба</td><td>5 в Г/Т-форме</td><td>Взрыв 3×3 (5×5 при комбо)</td></tr>
      <tr><td>🪩 Диско</td><td>5 в ряд</td><td>Убирает все фишки цвета соседа</td></tr>
    </table>
    <p>Спецфишка активируется свайпом на соседнюю фишку и расходуется.</p>
    <h3>Комбо — свайп двух спецфишек</h3>
    <ul>
      <li>🚀+🚀 — крест (строка + столбец), 800 очков</li>
      <li>🚀+💣 — 3 линии, 1000 очков</li>
      <li>💣+💣 — взрыв 7×7, 1200 очков</li>
      <li>🪩+🚀 / 🪩+💣 — все фишки цвета превращаются в ракеты/бомбы, 1500 очков</li>
      <li>🪩+🪩 — очистка всего поля, 5000 очков</li>
    </ul>
    <h3>Каскады</h3>
    <p>После уничтожения сверху падают новые фишки. Каждый каскад умножает очки на +20% (макс. 12 подряд). Рубите внизу поля — больше падений, больше каскадов.</p>
    <h3>Бустеры</h3>
    <ul>
      <li>🔨 Молот — уничтожает одну фишку</li>
      <li>➡️/⬇️ Метеор — чистит строку/столбец</li>
      <li>🔀 Shuffle — перемешать поле</li>
    </ul>
    <p>Бустеры покупаются за монеты в магазине. Жизни восстанавливаются: 1 жизнь за 3 часа, максимум 5.</p>
  </div>
</div>
@endsection

<script>
let authMode = 'login';
function toggleAuthMode() {
  authMode = authMode === 'login' ? 'register' : 'login';
  const confirmGroup = document.getElementById('confirmGroup');
  const secretGroup = document.getElementById('secretGroup');
  const secretAnswerGroup = document.getElementById('secretAnswerGroup');
  const rememberRow = document.getElementById('rememberRow');
  const mainBtn = document.getElementById('authMainBtn');
  const toggleBtn = document.getElementById('authToggleBtn');
  if (authMode === 'register') {
    confirmGroup.style.display = 'block';
    secretGroup.style.display = 'block';
    secretAnswerGroup.style.display = 'block';
    rememberRow.style.display = 'none';
    mainBtn.textContent = 'Создать аккаунт';
    mainBtn.setAttribute('onclick', 'handleRegister()');
    toggleBtn.textContent = 'Уже есть аккаунт? Войти';
  } else {
    confirmGroup.style.display = 'none';
    secretGroup.style.display = 'none';
    secretAnswerGroup.style.display = 'none';
    rememberRow.style.display = 'flex';
    mainBtn.textContent = 'Войти';
    mainBtn.setAttribute('onclick', 'handleLogin()');
    toggleBtn.textContent = 'Создать аккаунт';
  }
}
</script>