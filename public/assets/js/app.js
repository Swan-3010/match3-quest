/**
 * Match Quest — Main App Entry Point
 * SPA routing, screen management, game integration
 */

import { Match3Engine } from './game_engine_pixijs.js?v=20261029a';

const MAX_LIVES = 5;

// Единые коды бустеров (совпадают с кодами товаров магазина)
const BOOSTER_NAMES = {
  hammer: 'Молот',
  meteor_row: 'Метеор: строка',
  meteor_col: 'Метеор: столбец',
  cross_diag: 'Перекрёстный удар',
  shuffle: 'Перемешивание',
};
const SHOP_ICONS = {
  hammer: '🔨', meteor_row: '➡️', meteor_col: '⬇️', cross_diag: '✛', shuffle: '🔀',
  life_1: '❤️', lives_refill: '❤️‍🩹', gems_1: '💎',
};
const SHOP_TABS = [
  { category: 'imba', label: 'Бустеры' },
  { category: 'life', label: 'Жизни' },
  { category: 'gems', label: 'Гемы' },
];

class App {
  constructor() {
    this.screens = {};
    this.currentScreen = null;
    this.userProgress = null;
    this.gameEngine = null;
    this.config = null;
    this.userInventory = {};
    this._boostersUsed = [];
    this._selectedBooster = null;
    this._shopCategory = 'imba';
    this._helpReady = false;
    this._helpPromise = null;
    this._boosterInputBound = false;
    this._aimDownPos = null;
    this._hoverCell = null;
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

    // Инвентарь бустеров (гостю не страшно — подавим 401)
    await this.loadInventory();

    this.showScreen('menu');
  }

  showScreen(name) {
    document.querySelectorAll('.screen').forEach(s => s.classList.remove('active'));
    const screen = document.getElementById(`screen-${name}`);
    if (screen) {
      screen.classList.add('active');
      this.currentScreen = name;
    }
    if (name === 'map') this.loadMapWindow();
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
    if (lives) lives.textContent = `${this.userProgress.lives}/${MAX_LIVES}`;
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
      this._boostersUsed = [];
      this.cancelBooster();

      this.showScreen('game');
      this._ensureBoosterInput();

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
      this.updateBoostPanel();

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
      case 'boosterApplied':
        this._onBoosterApplied(data[0]);
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
        boosters_used: this._boostersUsed || [],
      });

      this.showResultScreen(win, score, stars, result.rewards);
      this.userProgress = await window.api.get('/user/progress');
      this.updateCurrencyBar();
      await this.loadInventory();
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

    // «Следующий» — только при победе; при поражении — «Заново»
    const nextBtn = document.getElementById('resultBtnNext');
    const retryBtn = document.getElementById('resultBtnRetry');
    if (nextBtn) nextBtn.style.display = win ? '' : 'none';
    if (retryBtn) retryBtn.style.display = win ? 'none' : '';
  }

  hideResult() {
    document.getElementById('resultOverlay').classList.remove('active');
  }

  // ===== BOOSTERS =====
  async loadInventory() {
    try {
      const inv = await window.api.get('/user/inventory');
      const map = {};
      (inv || []).forEach(i => { map[i.imba_code] = i.quantity; });
      this.userInventory = map;
    } catch (e) {
      console.warn('Failed to load inventory:', e);
    }
    this.updateBoostPanel();
  }

  updateBoostPanel() {
    const panel = document.getElementById('boostPanel');
    if (!panel) return;
    panel.querySelectorAll('.booster-slot').forEach(btn => {
      const code = btn.dataset.code;
      const qty = (this.userInventory && this.userInventory[code]) || 0;
      const badge = btn.querySelector('.booster-badge');
      if (badge) badge.textContent = '×' + qty;
      btn.classList.toggle('empty', qty <= 0);
    });
  }

  _boostButton(code) {
    return document.querySelector(`.booster-slot[data-code="${code}"]`);
  }

  selectBooster(code) {
    if (!this.gameEngine || this.currentScreen !== 'game') return;
    if (this.gameEngine.processing) {
      this.showToast('Подождите окончания хода');
      return;
    }
    const qty = (this.userInventory && this.userInventory[code]) || 0;
    if (qty <= 0) {
      this.showBoosterOffer(code);
      return;
    }
    if (code === 'shuffle') {
      // Перемешивание не требует выбора ячейки — применяем сразу
      this._applyBooster(code, -1, -1);
      return;
    }
    if (this._selectedBooster === code) {
      this.cancelBooster();
      return;
    }
    this.cancelBooster();
    this._selectedBooster = code;
    const btn = this._boostButton(code);
    btn?.classList.add('selected');
    document.getElementById('gameCanvasContainer')?.classList.add('booster-aiming');
    this.showToast('Нажмите на ячейку поля');
  }

  cancelBooster() {
    if (!this._selectedBooster) return;
    this._selectedBooster = null;
    document.querySelectorAll('.booster-slot.selected').forEach(b => b.classList.remove('selected'));
    document.getElementById('gameCanvasContainer')?.classList.remove('booster-aiming');
    this._hideBoosterHover();
  }

  // Постоянные обработчики на контейнере канваса: активны только в режиме прицеливания,
  // свайпам движка не мешаем (drag-«клики» игнорируем, input движка не трогаем).
  _ensureBoosterInput() {
    if (this._boosterInputBound) return;
    const container = document.getElementById('gameCanvasContainer');
    if (!container) return;
    this._boosterInputBound = true;

    container.addEventListener('pointerdown', (e) => {
      if (!this._selectedBooster) return;
      this._aimDownPos = { x: e.clientX, y: e.clientY };
    });

    container.addEventListener('pointermove', (e) => {
      if (!this._selectedBooster || !this.gameEngine) return;
      const cell = this._cellFromEvent(e);
      if (!cell) { this._hideBoosterHover(); return; }
      if (this._hoverCell && this._hoverCell.r === cell.r && this._hoverCell.c === cell.c) return;
      this._hoverCell = cell;
      this.gameEngine.renderer.showCellHighlight(cell.r, cell.c);
    });

    container.addEventListener('pointerleave', () => this._hideBoosterHover());

    container.addEventListener('click', (e) => {
      if (!this._selectedBooster || !this.gameEngine) return;
      // «Клик» после перетаскивания — это свайп движка, бустер не применяем
      if (this._aimDownPos) {
        const dx = e.clientX - this._aimDownPos.x;
        const dy = e.clientY - this._aimDownPos.y;
        this._aimDownPos = null;
        if (Math.sqrt(dx * dx + dy * dy) > 10) return;
      }
      const code = this._selectedBooster;
      const cell = this._cellFromEvent(e);
      if (!cell) { this.cancelBooster(); return; }
      this._applyBooster(code, cell.r, cell.c);
    });

    // Тап вне поля и панели отменяет прицеливание
    document.addEventListener('pointerdown', (e) => {
      if (!this._selectedBooster) return;
      const cont = document.getElementById('gameCanvasContainer');
      const panel = document.getElementById('boostPanel');
      if (cont?.contains(e.target) || panel?.contains(e.target)) return;
      this.cancelBooster();
    });
  }

  _cellFromEvent(e) {
    const container = document.getElementById('gameCanvasContainer');
    const renderer = this.gameEngine && this.gameEngine.renderer;
    if (!container || !renderer || !renderer.app) return null;
    const rect = container.getBoundingClientRect();
    const x = (e.clientX - rect.left) * (renderer.app.screen.width / rect.width);
    const y = (e.clientY - rect.top) * (renderer.app.screen.height / rect.height);
    const ts = renderer.tileSize;
    const gap = renderer.gap;
    const pad = renderer.padding;
    const c = Math.floor((x - pad) / (ts + gap));
    const r = Math.floor((y - pad) / (ts + gap));
    const N = 9; // CONFIG.GRID_SIZE
    if (r >= 0 && r < N && c >= 0 && c < N) return { r, c };
    return null;
  }

  _hideBoosterHover() {
    this._hoverCell = null;
    if (this.gameEngine && this.gameEngine.renderer) {
      this.gameEngine.renderer.clearCellHighlight();
    }
  }

  _applyBooster(code, r, c) {
    const engine = this.gameEngine;
    if (!engine || engine.processing) return;
    const btn = this._boostButton(code);
    this.cancelBooster();
    if (btn) {
      btn.classList.add('applying');
      btn.disabled = true;
    }
    (async () => {
      try {
        await engine._activateBooster(code, r, c);
        await this.loadInventory(); // серверное истинное количество
      } catch (err) {
        console.warn('booster apply failed:', err);
        this.showToast('Не удалось применить бустер');
      } finally {
        if (btn) {
          btn.classList.remove('applying');
          btn.disabled = false;
        }
        this.updateBoostPanel();
      }
    })();
  }

  _onBoosterApplied(type) {
    if (!type) return;
    this._boostersUsed = this._boostersUsed || [];
    this._boostersUsed.push(type);
    if (this.userInventory) {
      this.userInventory[type] = Math.max(0, (this.userInventory[type] || 0) - 1);
    }
    document.querySelectorAll('.booster-slot.applying').forEach(b => b.classList.remove('applying'));
    this.updateBoostPanel();
  }

  // Модалка «Купить бустер?»
  async showBoosterOffer(code) {
    const overlay = document.getElementById('boosterOffer');
    const text = document.getElementById('boosterOfferText');
    if (!overlay || !text) return;
    const name = BOOSTER_NAMES[code] || code;
    let priceText = '';
    try {
      const items = await window.api.get('/shop/items?category=imba');
      const item = (items || []).find(i => i.code === code);
      if (item) {
        priceText = item.price_currency === 'gems'
          ? `${item.price_gems} 💎`
          : `${item.price_coins} 🪙`;
      }
    } catch (e) { /* цена недоступна — покажем без неё */ }
    text.innerHTML = `Бустер «${name}» закончился.${priceText ? `<br>Купить за ${priceText}?` : ''}`;
    const buyBtn = document.getElementById('boosterOfferBuy');
    if (buyBtn) buyBtn.onclick = () => this._goShopFromOffer();
    overlay.classList.add('active');
  }

  hideBoosterOffer() {
    document.getElementById('boosterOffer')?.classList.remove('active');
  }

  _goShopFromOffer() {
    this.hideBoosterOffer();
    this.cancelBooster();
    this.showScreen('shop');
    this.loadShop('imba');
  }

  useBooster(type) {
    // Совместимость со старыми вызовами — теперь всё через панель
    this.selectBooster(type === 'row' ? 'meteor_row' : type === 'col' ? 'meteor_col' : type);
  }

  goBack() {
    if (this.gameEngine) {
      this.gameEngine.destroy();
      this.gameEngine = null;
    }
    this.showScreen('menu');
  }

  // ===== SHOP =====
  async loadShop(category = 'imba') {
    this._shopCategory = category;
    // Таб «boost» не существует: бустеры живут в категории imba
    const activeTab = SHOP_TABS.some(t => t.category === category) ? category : 'imba';
    document.querySelectorAll('.shop-tab').forEach(el => {
      el.classList.toggle('active', el.dataset.category === activeTab);
    });

    this.showLoading(true);
    try {
      const items = await window.api.get(`/shop/items?category=${activeTab}`);
      const grid = document.getElementById('shopGrid');
      if (!grid) return;
      grid.innerHTML = (items || []).map(item => this._shopCardHtml(item)).join('');
      // Обработчики — через addEventListener, без inline-onclick
      const cards = grid.querySelectorAll('.shop-card');
      (items || []).forEach((item, idx) => {
        const el = cards[idx];
        if (el && !el.disabled) {
          el.addEventListener('click', () => this.buyItem(item.code));
        }
      });
    } catch (e) {
      this.showToast(e.message);
    } finally {
      this.showLoading(false);
    }
  }

  _shopCardHtml(item) {
    const progress = this.userProgress || {};
    const isGemsPrice = item.price_currency === 'gems';
    const priceVal = isGemsPrice ? item.price_gems : item.price_coins;
    const priceText = isGemsPrice ? `💎 ${priceVal}` : `🪙 ${priceVal}`;

    let disabled = false;
    let title = '';
    if (item.category === 'life') {
      if ((progress.lives ?? 0) >= MAX_LIVES) {
        disabled = true;
        title = 'Жизни полны';
      }
    } else if (item.code === 'gems_1') {
      if ((progress.coins ?? 0) < priceVal) {
        disabled = true;
        title = `Нужно ${priceVal} 🪙`;
      }
    } else {
      const cur = isGemsPrice ? (progress.gems ?? 0) : (progress.coins ?? 0);
      if (cur < priceVal) {
        disabled = true;
        title = 'Не хватает средств';
      }
    }

    return `
      <button type="button" class="shop-card" ${disabled ? 'disabled' : ''} ${title ? `title="${title}"` : ''}>
        <div class="item-icon">${this.getItemIcon(item.code)}</div>
        <div class="item-name">${item.name}</div>
        <div class="item-desc">${(item.effect_json && item.effect_json.desc) || ''}</div>
        <div class="price ${isGemsPrice ? 'gem' : 'gold'}">${priceText}</div>
      </button>
    `;
  }

  async buyItem(code) {
    this.showLoading(true);
    try {
      const clientRequestId = (crypto.randomUUID && crypto.randomUUID())
        || `req-${Date.now()}-${Math.random().toString(36).slice(2)}`;
      const res = await window.api.post('/shop/purchase', {
        item_code: code,
        quantity: 1,
        client_request_id: clientRequestId,
      });
      // Баланс — серверный источник истины
      this.userProgress = Object.assign({}, this.userProgress, res.balance);
      this.updateCurrencyBar();
      await this.loadInventory();
      this.showToast('Покупка успешна!');
      this.loadShop(this._shopCategory); // перерисовать disabled-состояния
    } catch (e) {
      const msg = (e && e.message || '').toLowerCase();
      if (msg.includes('lives_full') || msg.includes('полн')) {
        this.showToast('Жизни полны');
      } else if (msg.includes('insufficient') || msg.includes('средств') || msg.includes('недостаточно')) {
        this.showToast('Не хватает средств');
      } else {
        this.showToast(e.message);
      }
    } finally {
      this.showLoading(false);
    }
  }

  getItemIcon(code) {
    return SHOP_ICONS[code] || '📦';
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
  async loadMapWindow() {
    const container = document.getElementById('mapContainer');
    try {
      const w = await window.api.get('/levels/map-window');
      // Пока шёл запрос, пользователь мог уйти с карты
      if (this.currentScreen !== 'map') return;
      this.renderMapWindow(w);
    } catch (e) {
      if (this.currentScreen === 'map') {
        this.showToast(e.message || 'Ошибка загрузки карты');
      }
    }
  }

  renderMapWindow(w) {
    const container = document.getElementById('mapContainer');
    container.innerHTML = '';

    w.levels.forEach((node, idx) => {
      const el = document.createElement('div');
      el.className = `level-node ${node.status}`;
      if (node.status === 'done') {
        el.innerHTML = `${node.number}<span class="node-stars">${'⭐'.repeat(node.stars)}</span>`;
        el.onclick = () => this.startLevel(node.number);
      } else if (node.status === 'current') {
        el.textContent = node.number;
        el.onclick = () => this.startLevel(node.number);
      } else {
        el.innerHTML = `${node.number} 🔒`;
        el.onclick = () => this.showToast('Пройдите предыдущие уровни');
      }
      container.appendChild(el);

      if (idx < w.levels.length - 1) {
        const line = document.createElement('div');
        line.className = `path-line ${node.status === 'done' ? 'done' : ''}`;
        container.appendChild(line);
      }
    });

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

  // ===== HELP =====
  // Справка приходит с сервера (GET /help, публичный) и кэшируется на сессию.
  // Рендер: нативный аккордеон <details> по секциям.
  async ensureHelp() {
    const box = document.getElementById('helpContent');
    if (!box || this._helpReady) return;
    if (!this._helpPromise) {
      this._helpPromise = window.api.get('/help').catch(() => null);
    }
    const data = await this._helpPromise;
    if (!data || !Array.isArray(data.sections)) {
      box.innerHTML = '<p style="color:var(--text-3);text-align:center;">Справка временно недоступна. Попробуйте позже.</p>';
      this._helpReady = true;
      return;
    }
    box.innerHTML = data.sections.map(s => `
      <details class="help-section">
        <summary>${s.title}</summary>
        <div class="help-section-body">${s.html}</div>
      </details>
    `).join('');
    this._helpReady = true;
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
