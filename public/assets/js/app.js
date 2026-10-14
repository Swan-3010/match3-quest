/**
 * Match Quest — Main App Entry Point
 * SPA routing, screen management, game integration
 */

import { Match3Engine } from './game_engine_pixijs.js?v=20261014a';

class App {
  constructor() {
    this.screens = {};
    this.currentScreen = null;
    this.userProgress = null;
    this.gameEngine = null;
    this.config = null;
  }

  async init() {
    // Load cached config
    this.config = await window.api.loadConfig();

    // Check auth
    if (!window.auth.isAuthenticated()) {
      this.showScreen('auth');
      return;
    }

    // Load user progress
    try {
      this.userProgress = await window.api.get('/user/progress');
      this.updateCurrencyBar();
    } catch (e) {
      console.warn('Failed to load progress:', e);
    }

    this.showScreen('menu');
  }

  maxLevelReached() {
    const p = this.userProgress;
    if (!p) return 0;
    return p.max_level_reached ?? p.progress?.max_level_reached ?? 0;
  }

  showScreen(name) {
    document.querySelectorAll('.screen').forEach(s => s.classList.remove('active'));
    const screen = document.getElementById(`screen-${name}`);
    if (screen) {
      screen.classList.add('active');
      this.currentScreen = name;
    }
    if (name === 'map') this.renderMap(this.maxLevelReached());
    this.updateNav(name);
  }

  updateNav(activeName) {
    document.querySelectorAll('.nav-item').forEach(el => {
      el.classList.toggle('active', el.dataset.screen === activeName);
    });
  }

  updateCurrencyBar() {
    if (!this.userProgress) return;
    const lives = document.getElementById('currLives');
    const coins = document.getElementById('currCoins');
    const gems = document.getElementById('currGems');
    if (lives) lives.textContent = `${this.userProgress.lives}/5`;
    if (coins) coins.textContent = this.userProgress.coins?.toLocaleString() || '0';
    if (gems) gems.textContent = this.userProgress.gems || '0';
  }

  // ===== GAME =====
  async startLevel(levelId) {
    this.showLoading(true);
    try {
      const session = await window.api.post(`/levels/${levelId}/start`);
      const level = session.level;

      // ВАЖНО: фиксируем состояние ДО любых DOM-операций
      this.currentLevelId = levelId;
      this.sessionId = session.session_id;

      this.showScreen('game');

      // Init PixiJS engine
      const canvasContainer = document.getElementById('gameCanvasContainer');
      if (this.gameEngine) this.gameEngine.destroy();

      this.gameEngine = new Match3Engine(canvasContainer, (event, ...data) => {
        this.onGameEvent(event, ...data);
      });

      await this.gameEngine.init({
        level: levelId,
        moves: level.moves_limit,
        target: level.target_score,
        colors: level.colors_count,
        seed: session.board_seed,
      });

      // Update game UI
      document.getElementById('gameTarget').textContent = level.target_score.toLocaleString();
      document.getElementById('gameMoves').textContent = level.moves_limit;
      document.getElementById('gameScore').textContent = '0';
      const gl = document.getElementById('gameLevel');
      if (gl) gl.textContent = `Уровень ${levelId}`;

    } catch (e) {
      this.showToast(e.message || 'Ошибка загрузки уровня');
    } finally {
      this.showLoading(false);
    }
  }

  onGameEvent(event, ...data) {
    switch (event) {
      case 'scoreChanged':
        document.getElementById('gameScore').textContent = data[0].toLocaleString();
        break;
      case 'movesChanged':
        document.getElementById('gameMoves').textContent = data[0];
        break;
      case 'levelComplete':
        this.completeLevel(true, data[0], data[1]);
        break;
      case 'levelFailed':
        this.completeLevel(false, data[0], 0);
        break;
      case 'combo':
        this.showToast(`Комбо: ${data[0]} + ${data[1]}!`);
        break;
    }
  }

  async completeLevel(win, score, movesLeft) {
    if (!this.currentLevelId || !this.sessionId) {
      console.error('completeLevel: потеряны currentLevelId/sessionId', this.currentLevelId, this.sessionId);
      this.showToast('Ошибка: сессия уровня потеряна. Начните уровень заново.');
      return;
    }
    this.showLoading(true);
    try {
      const stars = win ? (score >= this.gameEngine.state.target * 1.6 ? 3 : score >= this.gameEngine.state.target * 1.3 ? 2 : 1) : 0;
      const result = await window.api.post(`/levels/${this.currentLevelId}/complete`, {
        session_id: this.sessionId,
        score: score,
        moves_used: this.gameEngine.state.moves_limit - movesLeft,
        stars: stars,
        result: win ? 'win' : 'fail',
        moves_log: this.gameEngine.state.moves_log || [],
        client_hash: 'stub',
      });

      this.showResultScreen(win, score, stars, result.rewards);
      this.userProgress = await window.api.get('/user/progress');
      this.updateCurrencyBar();
    } catch (e) {
      this.showToast(e.message);
    } finally {
      this.showLoading(false);
    }
  }

  showResultScreen(win, score, stars, rewards) {
    const overlay = document.getElementById('resultOverlay');
    const title = document.getElementById('resultTitle');
    const icon = document.getElementById('resultIcon');
    const starsEl = document.getElementById('resultStars');
    const scoreEl = document.getElementById('resultScore');
    const rewardsEl = document.getElementById('resultRewards');

    overlay.classList.add('active');
    title.textContent = win ? 'Победа!' : 'Неудача';
    icon.textContent = win ? '🏆' : '💔';
    starsEl.textContent = win ? '⭐'.repeat(stars) : '☆☆☆';
    scoreEl.textContent = score.toLocaleString();
    rewardsEl.innerHTML = rewards.map(r => `<span>${r.type}: +${r.qty}</span>`).join(' ');
  }

  hideResult() {
    document.getElementById('resultOverlay').classList.remove('active');
  }

  // ===== BOOSTERS =====
  useBooster(type) {
    if (!this.gameEngine || this.gameEngine.processing) {
      this.showToast('Подождите окончания хода');
      return;
    }
    this._pendingBooster = type;
    this.showToast('Нажмите на ячейку поля');
    const container = document.getElementById('gameCanvasContainer');
    // Remove old listener if any
    if (this._boosterHandler) {
      container.removeEventListener('click', this._boosterHandler);
    }
    this._boosterHandler = (e) => {
      const rect = container.getBoundingClientRect();
      const renderer = this.gameEngine.renderer;
      const ts = renderer.tileSize;
      const gap = renderer.gap;
      const pad = renderer.padding;
      const x = (e.clientX - rect.left) * (renderer.app.screen.width / rect.width);
      const y = (e.clientY - rect.top) * (renderer.app.screen.height / rect.height);
      const c = Math.floor((x - pad) / (ts + gap));
      const r = Math.floor((y - pad) / (ts + gap));
      if (r >= 0 && r < 9 && c >= 0 && c < 9) {
        this.gameEngine._activateBooster(this._pendingBooster, r, c);
      }
      this._pendingBooster = null;
      container.removeEventListener('click', this._boosterHandler);
      this._boosterHandler = null;
    };
    container.addEventListener('click', this._boosterHandler);
  }

  goBack() {
    if (this.gameEngine) {
      this.gameEngine.destroy();
      this.gameEngine = null;
    }
    this.showScreen('menu');
  }

  // ===== SHOP =====
  async loadShop(category = null) {
    this.showLoading(true);
    try {
      const items = await window.api.get(`/shop/items?category=${category || ''}`);
      const grid = document.getElementById('shopGrid');
      grid.innerHTML = items.map(item => `
        <div class="shop-card" onclick="app.buyItem('${item.code}', '${item.category}')">
          <div class="item-icon">${this.getItemIcon(item.category)}</div>
          <div class="item-name">${item.name}</div>
          <div class="item-desc">${item.effect_json?.desc || ''}</div>
          <div class="price ${item.price_coins ? 'gold' : 'gem'}">
            ${item.price_coins ? '🪙 ' + item.price_coins : '💎 ' + item.price_gems}
          </div>
        </div>
      `).join('');
    } catch (e) {
      this.showToast(e.message);
    } finally {
      this.showLoading(false);
    }
  }

  async buyItem(code, category) {
    this.showLoading(true);
    try {
      const currency = category === 'gems' ? 'gems' : 'coins';
      const res = await window.api.post('/shop/purchase', {
        item_code: code, quantity: 1, currency: currency
      });
      this.userProgress = res.balance;
      this.updateCurrencyBar();
      this.showToast('Покупка успешна!');
    } catch (e) {
      this.showToast(e.message);
    } finally {
      this.showLoading(false);
    }
  }

  getItemIcon(category) {
    const icons = { imba: '🔨', boost: '⚡', life: '❤️', gems: '💎' };
    return icons[category] || '📦';
  }

  // ===== PROFILE =====
  async loadProfile() {
    this.showLoading(true);
    try {
      const profile = await window.api.get('/user/profile');
      const user = profile?.user || {};
      const progress = profile?.progress || {};
      document.getElementById('profName').textContent = user.username || 'Игрок';
      document.getElementById('profLevel').textContent = `Уровень ${progress.player_level || 1}`;
      document.getElementById('profXp').textContent = `${progress.current_xp || 0} / ${progress.xp_for_next_level || 5000} XP`;
      document.getElementById('profLevels').textContent = progress.max_level_reached || 0;
      document.getElementById('profScore').textContent = (progress.total_score || 0).toLocaleString();
      document.getElementById('profStars').textContent = progress.total_stars || 0;
    } catch (e) {
      this.showToast(e.message || 'Ошибка загрузки профиля');
    } finally {
      this.showLoading(false);
    }
  }

  // ===== DAILY =====
  async loadDaily() {
    try {
      const status = await window.api.get('/daily/status');
      document.getElementById('dailyStreak').textContent = status.current_streak;
      const btn = document.getElementById('dailyClaimBtn');
      btn.disabled = !status.can_claim_today;
      btn.textContent = status.can_claim_today ? 'Забрать награду' : 'Уже получено';
    } catch (e) {
      console.warn(e);
    }
  }

  async claimDaily() {
    try {
      const res = await window.api.post('/daily/claim');
      this.showToast(`+${res.reward.qty} ${res.reward.type}!`);
      this.userProgress = await window.api.get('/user/progress');
      this.updateCurrencyBar();
      this.loadDaily();
    } catch (e) {
      this.showToast(e.message);
    }
  }

  // ===== MAP =====
  renderMap(maxLevel) {
    const container = document.getElementById('mapContainer');
    container.innerHTML = '';
    const current = maxLevel + 1;
    for (let i = 1; i <= Math.min(current + 4, 500); i++) {
      const isCompleted = i <= maxLevel;
      const isCurrent = i === current;
      const isLocked = i > current;

      const node = document.createElement('div');
      node.className = `level-node ${isCompleted ? 'completed' : isCurrent ? 'current' : 'locked'}`;
      node.innerHTML = isLocked ? `${i} 🔒` : `${i}`;
      if (!isLocked) {
        node.onclick = () => this.startLevel(i);
      }
      container.appendChild(node);

      if (i < 500) {
        const line = document.createElement('div');
        line.className = `path-line ${isCompleted ? 'done' : ''}`;
        container.appendChild(line);
      }
    }
    // Scroll to current
    setTimeout(() => {
      const current = container.querySelector('.current');
      if (current) current.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }, 100);
  }

  // ===== UI HELPERS =====
  showLoading(show) {
    document.getElementById('loadingOverlay').classList.toggle('active', show);
  }

  showToast(msg) {
    const toast = document.getElementById('toast');
    toast.textContent = msg;
    toast.classList.add('show');
    setTimeout(() => toast.classList.remove('show'), 2500);
  }

  async installPwa() {
    if (deferredInstallPrompt) {
      deferredInstallPrompt.prompt();
      try {
        const choice = await deferredInstallPrompt.userChoice;
        if (choice.outcome === 'accepted') {
          const btn = document.getElementById('installBtn');
          if (btn) btn.style.display = 'none';
        }
      } catch (e) { /* пользователь закрыл диалог */ }
      deferredInstallPrompt = null;
      return;
    }
    const isIos = /iP(hone|ad|od)/.test(navigator.userAgent)
      || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
    this.showToast(isIos
      ? 'iPhone/iPad: Поделиться → «На экран Домой»'
      : 'Меню браузера (⋮) → «Установить приложение»');
  }
}

window.App = App;
window.app = new App();

// PWA install: ловим событие раньше всех (авто-подсказка Chrome гасится,
// показываем свой вход в меню)
let deferredInstallPrompt = null;
window.addEventListener('beforeinstallprompt', (e) => {
  e.preventDefault();
  deferredInstallPrompt = e;
});

// Init on DOM ready
document.addEventListener('DOMContentLoaded', () => {
  window.app.init();
});

// Auth event handlers
window.addEventListener('auth:logout', () => {
  window.app.showScreen('auth');
});

window.addEventListener('appinstalled', () => {
  deferredInstallPrompt = null;
  const btn = document.getElementById('installBtn');
  if (btn) btn.style.display = 'none';
  window.app.showToast('Приложение установлено!');
});
