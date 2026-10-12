/**
 * Match Quest — PixiJS v7 Game Engine
 * Поле 9×9, match-3, спецфишки, комбо, каскады
 * ES Module, production-ready
 */

import { Application, Container, Graphics, Text, Sprite, Assets } from 'https://cdn.jsdelivr.net/npm/pixi.js@7/dist/pixi.mjs';

// ============================================================
// CONFIG
// ============================================================
const CONFIG = {
  GRID_SIZE: 9,
  COLORS: 5,
  BASE_SCORE_MATCH_3: 60,
  BASE_SCORE_MATCH_4: 150,
  BASE_SCORE_MATCH_5: 300,
  CASCADE_BONUS: 0.20,
  SWAP_DURATION: 150,
  DESTROY_DURATION: 200,
  FALL_DURATION: 300,
  SPAWN_DURATION: 200,
  TILE_RADIUS: 6,
  GAP: 3,
};

const COLOR_DEFS = [
  { id: 0, name: 'red',    hex: 0xef4444, hexLight: 0xf87171, hexDark: 0xb91c1c, emoji: '🔴' },
  { id: 1, name: 'blue',   hex: 0x3b82f6, hexLight: 0x60a5fa, hexDark: 0x1d4ed8, emoji: '🔵' },
  { id: 2, name: 'green',  hex: 0x22c55e, hexLight: 0x4ade80, hexDark: 0x15803d, emoji: '🟢' },
  { id: 3, name: 'yellow', hex: 0xeab308, hexLight: 0xfacc15, hexDark: 0xa16207, emoji: '🟡' },
  { id: 4, name: 'purple', hex: 0xa855f7, hexLight: 0xc084fc, hexDark: 0x7e22ce, emoji: '🟣' },
];

const SPECIAL_DEFS = {
  ROCKET_H: { code: 'ROCKET_H', color: 0x6366f1, name: 'rocket_h', clear: 'row' },
  ROCKET_V: { code: 'ROCKET_V', color: 0x6366f1, name: 'rocket_v', clear: 'col' },
  BOMB:     { code: 'BOMB',     color: 0xf97316, name: 'bomb',     clear: 'area3x3' },
  DISCO:    { code: 'DISCO',    color: 0xec4899, name: 'disco',    clear: 'color' },
};

const COMBO_MATRIX = {
  'ROCKET_H+ROCKET_H': { effect: 'cross', score: 800 },
  'ROCKET_H+ROCKET_V': { effect: 'cross', score: 800 },
  'ROCKET_V+ROCKET_V': { effect: 'cross', score: 800 },
  'ROCKET_H+BOMB':     { effect: 'lines3', score: 1000 },
  'ROCKET_V+BOMB':     { effect: 'lines3', score: 1000 },
  'BOMB+BOMB':         { effect: 'area7x7', score: 1200 },
  'DISCO+ROCKET_H':    { effect: 'colorToRockets', score: 1500 },
  'DISCO+ROCKET_V':    { effect: 'colorToRockets', score: 1500 },
  'DISCO+BOMB':        { effect: 'colorToBombs', score: 1500 },
  'DISCO+DISCO':       { effect: 'clearAll', score: 5000 },
};

// ============================================================
// GAME STATE
// ============================================================
class GameState {
  constructor() {
    this.grid = []; // [row][col] = Tile
    this.score = 0;
    this.moves = 0;
    this.moves_limit = 0;
    this.moves_log = [];
    this.target = 0;
    this.level = 1;
    this.selectedTile = null;
    this.isAnimating = false;
    this.cascadeCount = 0;
  }

  init(levelConfig) {
    this.level = levelConfig.level;
    this.moves = levelConfig.moves;
    this.moves_limit = levelConfig.moves;
    this.target = levelConfig.target;
    this.score = 0;
    this.cascadeCount = 0;
    this.moves_log = [];
    this._spawnGrid(levelConfig.colors || CONFIG.COLORS);
    this._ensureNoInitialMatches();
    this._ensureHasMove();
  }

  _spawnGrid(colorCount) {
    this.grid = [];
    for (let r = 0; r < CONFIG.GRID_SIZE; r++) {
      this.grid[r] = [];
      for (let c = 0; c < CONFIG.GRID_SIZE; c++) {
        let color;
        do {
          color = Math.floor(Math.random() * colorCount);
        } while (
          (c >= 2 && this.grid[r][c-1].color === color && this.grid[r][c-2].color === color) ||
          (r >= 2 && this.grid[r-1][c].color === color && this.grid[r-2][c].color === color)
        );
        this.grid[r][c] = new Tile(r, c, color);
      }
    }
  }

  _ensureNoInitialMatches() {
    // Already handled in spawn
  }

  _ensureHasMove() {
    if (!this.hasAnyMove()) {
      this.shuffle();
    }
  }

  hasAnyMove() {
    for (let r = 0; r < CONFIG.GRID_SIZE; r++) {
      for (let c = 0; c < CONFIG.GRID_SIZE; c++) {
        if (c < CONFIG.GRID_SIZE - 1 && this._wouldMatch(r, c, r, c + 1)) return true;
        if (r < CONFIG.GRID_SIZE - 1 && this._wouldMatch(r, c, r + 1, c)) return true;
      }
    }
    return false;
  }

  _wouldMatch(r1, c1, r2, c2) {
    this._swap(r1, c1, r2, c2);
    const has = this.findMatches().length > 0;
    this._swap(r1, c1, r2, c2); // swap back
    return has;
  }

  _swap(r1, c1, r2, c2) {
    const tmp = this.grid[r1][c1].color;
    this.grid[r1][c1].color = this.grid[r2][c2].color;
    this.grid[r2][c2].color = tmp;
    const tmpS = this.grid[r1][c1].special;
    this.grid[r1][c1].special = this.grid[r2][c2].special;
    this.grid[r2][c2].special = tmpS;
  }

  findMatches() {
    const matches = [];
    const seen = new Set();

    // Horizontal
    for (let r = 0; r < CONFIG.GRID_SIZE; r++) {
      let count = 1, start = 0;
      for (let c = 1; c <= CONFIG.GRID_SIZE; c++) {
        const curr = c < CONFIG.GRID_SIZE ? this.grid[r][c] : null;
        const prev = this.grid[r][start];
        if (curr && !curr.special && !prev.special && curr.color !== -1 && prev.color !== -1 && curr.color === prev.color) {
          count++;
        } else {
          if (count >= 3) {
            for (let i = 0; i < count; i++) {
              const key = `${r},${start + i}`;
              if (!seen.has(key)) { seen.add(key); matches.push({ r, c: start + i }); }
            }
          }
          start = c; count = 1;
        }
      }
    }

    // Vertical
    for (let c = 0; c < CONFIG.GRID_SIZE; c++) {
      let count = 1, start = 0;
      for (let r = 1; r <= CONFIG.GRID_SIZE; r++) {
        const curr = r < CONFIG.GRID_SIZE ? this.grid[r][c] : null;
        const prev = this.grid[start][c];
        if (curr && !curr.special && !prev.special && curr.color !== -1 && prev.color !== -1 && curr.color === prev.color) {
          count++;
        } else {
          if (count >= 3) {
            for (let i = 0; i < count; i++) {
              const key = `${start + i},${c}`;
              if (!seen.has(key)) { seen.add(key); matches.push({ r: start + i, c }); }
            }
          }
          start = r; count = 1;
        }
      }
    }

    return matches;
  }

  checkSpecialForm(matches) {
    if (matches.length < 4) return null;
    const rows = new Set(matches.map(m => m.r));
    const cols = new Set(matches.map(m => m.c));

    if (matches.length === 4) {
      if (rows.size === 1) return 'ROCKET_H';
      if (cols.size === 1) return 'ROCKET_V';
    }
    if (matches.length >= 5) {
      if (rows.size === 1 || cols.size === 1) return 'DISCO';
    }
    if (matches.length === 5 && rows.size >= 2 && cols.size >= 2) return 'BOMB';
    return null;
  }

  getComboKey(sp1, sp2) {
    const keys = [`${sp1}+${sp2}`, `${sp2}+${sp1}`];
    for (const k of keys) if (COMBO_MATRIX[k]) return k;
    return null;
  }

  shuffle() {
    const colorCount = this.level >= 100 ? 6 : CONFIG.COLORS;
    this._spawnGrid(colorCount);
    this._ensureHasMove();
  }
}

// ============================================================
// TILE
// ============================================================
class Tile {
  constructor(r, c, color, special = null) {
    this.r = r; this.c = c;
    this.color = color;
    this.special = special;
    this.ice = false;
    this.stone = false;
    this.lock = false;
    this.visual = null; // Pixi DisplayObject
    this.animOffset = { x: 0, y: 0 };
    this.scale = 1;
    this.alpha = 1;
  }

  isObstacle() {
    return this.ice || this.stone || this.lock;
  }

  isEmpty() {
    return this.color === -1 && !this.special;
  }
}

// ============================================================
// RENDERER
// ============================================================
class GameRenderer {
  constructor(canvasContainer, onEvent) {
    this.container = canvasContainer;
    this.onEvent = onEvent;
    this.app = null;
    this.boardContainer = null;
    this.tileSize = 36;
    this.gap = CONFIG.GAP;
    this.padding = 8;
    this.particles = [];
    this.floatingTexts = [];
  }

  async init() {
    const rect = this.container.getBoundingClientRect();
    const size = Math.min(rect.width, rect.height, 400);

    this.app = new Application({
      width: size,
      height: size,
      backgroundColor: 0x12121a,
      resolution: window.devicePixelRatio || 1,
      autoDensity: true,
      antialias: true,
    });

    this.container.appendChild(this.app.view);
    this.tileSize = Math.floor((size - this.padding * 2 - this.gap * (CONFIG.GRID_SIZE - 1)) / CONFIG.GRID_SIZE);

    this.boardContainer = new Container();
    this.app.stage.addChild(this.boardContainer);

    // Particle layer
    this.particleContainer = new Container();
    this.app.stage.addChild(this.particleContainer);

    // Animation ticker
    this.app.ticker.add(() => this._updateAnimations());

    // Input
    this._setupInput();
  }

  _setupInput() {
    let dragStart = null;

    this.app.view.addEventListener('pointerdown', (e) => {
      const t = this._getTileAt(e.clientX, e.clientY);
      if (t) dragStart = { x: e.clientX, y: e.clientY, r: t.r, c: t.c };
    });

    this.app.view.addEventListener('pointermove', (e) => {
      if (!dragStart) return;
      const dx = e.clientX - dragStart.x;
      const dy = e.clientY - dragStart.y;
      if (Math.sqrt(dx*dx + dy*dy) > 15) {
        let nr = dragStart.r, nc = dragStart.c;
        if (Math.abs(dx) > Math.abs(dy)) nc += dx > 0 ? 1 : -1;
        else nr += dy > 0 ? 1 : -1;
        if (nr >= 0 && nr < CONFIG.GRID_SIZE && nc >= 0 && nc < CONFIG.GRID_SIZE) {
          this.onEvent('swap', dragStart.r, dragStart.c, nr, nc);
        }
        dragStart = null;
      }
    });

    this.app.view.addEventListener('pointerup', () => { dragStart = null; });
  }

  _getTileAt(clientX, clientY) {
    const rect = this.app.view.getBoundingClientRect();
    const x = (clientX - rect.left) * (this.app.screen.width / rect.width);
    const y = (clientY - rect.top) * (this.app.screen.height / rect.height);
    const c = Math.floor((x - this.padding) / (this.tileSize + this.gap));
    const r = Math.floor((y - this.padding) / (this.tileSize + this.gap));
    if (r >= 0 && r < CONFIG.GRID_SIZE && c >= 0 && c < CONFIG.GRID_SIZE) return { r, c };
    return null;
  }

  renderGrid(grid) {
    this.boardContainer.removeChildren();
    for (let r = 0; r < CONFIG.GRID_SIZE; r++) {
      for (let c = 0; c < CONFIG.GRID_SIZE; c++) {
        const tile = grid[r][c];
        const sprite = this._createTileSprite(tile);
        tile.visual = sprite;
        this.boardContainer.addChild(sprite);
      }
    }
  }

  _createTileSprite(tile) {
    const container = new Container();
    const x = this.padding + tile.c * (this.tileSize + this.gap);
    const y = this.padding + tile.r * (this.tileSize + this.gap);
    container.x = x;
    container.y = y;

    const g = new Graphics();
    const ts = this.tileSize;

    if (tile.special) {
      const def = SPECIAL_DEFS[tile.special]
        || SPECIAL_DEFS[String(tile.special).replace('_H', '').replace('_V', '')]
        || { color: 0xffd700 };
      g.beginFill(def.color);
      g.drawRoundedRect(2, 2, ts - 4, ts - 4, CONFIG.TILE_RADIUS);
      g.endFill();
      // Glow
      g.lineStyle(2, 0xffffff, 0.3);
      g.drawRoundedRect(2, 2, ts - 4, ts - 4, CONFIG.TILE_RADIUS);
    } else {
      const color = COLOR_DEFS[tile.color];
      // Gradient simulation
      g.beginFill(color.hex);
      g.drawRoundedRect(2, 2, ts - 4, ts - 4, CONFIG.TILE_RADIUS);
      g.endFill();
      // Highlight
      g.beginFill(0xffffff, 0.15);
      g.drawRoundedRect(2, 2, ts - 4, (ts - 4) * 0.5, CONFIG.TILE_RADIUS);
      g.endFill();
    }

    container.addChild(g);

    // Emoji text
    const text = new Text(
      tile.special ? '⭐' : COLOR_DEFS[tile.color]?.emoji || '',
      {
        fontSize: ts * 0.5,
        fill: 0xffffff,
        align: 'center',
      }
    );
    text.anchor.set(0.5);
    text.x = ts / 2;
    text.y = ts / 2;
    container.addChild(text);

    // Ice overlay
    if (tile.ice) {
      const ice = new Graphics();
      ice.beginFill(0xa5f3fc, 0.5);
      ice.drawRoundedRect(0, 0, ts, ts, CONFIG.TILE_RADIUS);
      ice.endFill();
      container.addChild(ice);
    }

    return container;
  }

  animateSwap(r1, c1, r2, c2, onComplete) {
    const t1 = this._getVisual(r1, c1);
    const t2 = this._getVisual(r2, c2);
    if (!t1 || !t2) { onComplete(); return; }

    const start1 = { x: t1.x, y: t1.y };
    const start2 = { x: t2.x, y: t2.y };
    const startTime = Date.now();

    const animate = () => {
      const p = Math.min(1, (Date.now() - startTime) / CONFIG.SWAP_DURATION);
      const ease = this._easeOutQuad(p);
      t1.x = start1.x + (start2.x - start1.x) * ease;
      t1.y = start1.y + (start2.y - start1.y) * ease;
      t2.x = start2.x + (start1.x - start2.x) * ease;
      t2.y = start2.y + (start1.y - start2.y) * ease;

      if (p < 1) requestAnimationFrame(animate);
      else onComplete();
    };
    animate();
  }

  animateDestroy(tiles, onComplete) {
    let completed = 0;
    const totalTiles = tiles.length;
    const doneDestroy = () => { if (completed >= totalTiles) onComplete(); };
    tiles.forEach(t => {
      const vis = this._getVisual(t.r, t.c);
      if (!vis) { completed++; doneDestroy(); return; }

      this.spawnParticles(t.r, t.c, t.color);

      const startTime = Date.now();
      const animate = () => {
        const p = Math.min(1, (Date.now() - startTime) / CONFIG.DESTROY_DURATION);
        vis.scale.set(1 - p);
        vis.alpha = 1 - p;
        if (p < 1) requestAnimationFrame(animate);
        else {
          try { this.boardContainer.removeChild(vis); } catch(e) {}
          completed++; doneDestroy();
        }
      };
      animate();
    });
    if (tiles.length === 0) onComplete();
  }

  animateFall(moves, onComplete) {
    // moves = [{from: {r,c}, to: {r,c}}]
    let completed = 0;
    const totalMoves = moves.length;
    const doneFall = () => { if (completed >= totalMoves) onComplete(); };
    moves.forEach(m => {
      const vis = m.vis || this._getVisual(m.from.r, m.from.c);
      if (!vis) { completed++; doneFall(); return; }
      const targetY = this.padding + m.to.r * (this.tileSize + this.gap);
      const startY = vis.y;
      const startTime = Date.now();

      const animate = () => {
        const p = Math.min(1, (Date.now() - startTime) / CONFIG.FALL_DURATION);
        const ease = this._easeOutBounce(p);
        vis.y = startY + (targetY - startY) * ease;
        if (p < 1) requestAnimationFrame(animate);
        else { completed++; doneFall(); }
      };
      animate();
    });
    if (moves.length === 0) onComplete();
  }

  spawnParticles(r, c, colorIdx) {
    const cx = this.padding + c * (this.tileSize + this.gap) + this.tileSize / 2;
    const cy = this.padding + r * (this.tileSize + this.gap) + this.tileSize / 2;
    const col = COLOR_DEFS[colorIdx]?.hex || 0xffffff;

    for (let i = 0; i < 8; i++) {
      const p = new Graphics();
      p.beginFill(col);
      p.drawCircle(0, 0, 3 + Math.random() * 3);
      p.endFill();
      p.x = cx; p.y = cy;
      const angle = (Math.PI * 2 / 8) * i;
      p.vx = Math.cos(angle) * (2 + Math.random() * 2);
      p.vy = Math.sin(angle) * (2 + Math.random() * 2);
      p.life = 1;
      this.particleContainer.addChild(p);
      this.particles.push(p);
    }
  }

  _updateAnimations() {
    for (let i = this.particles.length - 1; i >= 0; i--) {
      const p = this.particles[i];
      p.x += p.vx; p.y += p.vy;
      p.vy += 0.1; // gravity
      p.life -= 0.03;
      p.alpha = p.life;
      p.scale.set(p.life);
      if (p.life <= 0) {
        this.particleContainer.removeChild(p);
        this.particles.splice(i, 1);
      }
    }
  }

  _getVisual(r, c) {
    // Find by position
    for (const child of this.boardContainer.children) {
      const tx = Math.round((child.x - this.padding) / (this.tileSize + this.gap));
      const ty = Math.round((child.y - this.padding) / (this.tileSize + this.gap));
      if (tx === c && ty === r) return child;
    }
    return null;
  }

  _easeOutQuad(t) { return t * (2 - t); }
  _easeOutBounce(t) {
    if (t < 1/2.75) return 7.5625*t*t;
    if (t < 2/2.75) return 7.5625*(t-=1.5/2.75)*t+0.75;
    if (t < 2.5/2.75) return 7.5625*(t-=2.25/2.75)*t+0.9375;
    return 7.5625*(t-=2.625/2.75)*t+0.984375;
  }

  destroy() {
    if (this.app) this.app.destroy(true);
  }
}

// ============================================================
// GAME CONTROLLER
// ============================================================
export class Match3Engine {
  constructor(canvasContainer, eventCallback) {
    this.state = new GameState();
    this.renderer = new GameRenderer(canvasContainer, (type, ...args) => this._onInput(type, ...args));
    this.eventCallback = eventCallback;
    this.processing = false;
  }

  async init(levelConfig) {
    this._completeSent = false;
    if (!this._watchdogStarted) {
      this._watchdogStarted = true;
      // Фоновый сторож: поле всегда самовосстанавливается
      setInterval(() => {
        try {
          if (this.processing) {
            this._stuckSeconds = (this._stuckSeconds || 0) + 3;
            if (this._stuckSeconds >= 15) {
              console.error('engine: processing stuck 15s — force reset');
              this.processing = false;
              this._stuckSeconds = 0;
              this.repairSync();
            }
          } else {
            this._stuckSeconds = 0;
            // FIX: лечим поле ТОЛЬКО когда не в процессе хода
            if (this.renderer && this.renderer.boardContainer && this.state && this.state.grid.length) {
            try {
              const GR = CONFIG.GRID_SIZE;
              let holes = 0;
              for (let r = 0; r < GR; r++) {
                for (let c = 0; c < GR; c++) {
                  const t = this.state.grid[r][c];
                  if (t.color === -1 && !t.special) holes++;
                }
              }
              if (holes > 0) { console.warn('watchdog: filling', holes, 'holes'); this._fillStateHoles(); }
              this.repairSync();
            } catch (e) { console.error('watchdog heal:', e); }
          }
        } catch (e) { console.error('watchdog error', e); }
      }, 3000);
    }
    await this.renderer.init();
    this.state.init(levelConfig);
    // Стартовые дыры: если генерация оставила пустые ячейки — досыпаем сразу
    let startEmpty = false;
    for (let r = 0; r < CONFIG.GRID_SIZE && !startEmpty; r++) {
      for (let c = 0; c < CONFIG.GRID_SIZE; c++) {
        if (this.state.grid[r][c].isEmpty()) { startEmpty = true; break; }
      }
    }
    if (startEmpty) await this._applyGravity();
    this.renderer.renderGrid(this.state.grid);
    this.repairSync(); // лечим возможный стартовый рассинхрон спрайтов
  }

  _onInput(type, r1, c1, r2, c2) {
    if (type === 'swap') this._handleSwap(r1, c1, r2, c2);
  }

  async _handleSwap(r1, c1, r2, c2) {
    if (this.processing || this.state.moves <= 0) return;
    if (Math.abs(r1 - r2) + Math.abs(c1 - c2) !== 1) return;

    const t1 = this.state.grid[r1][c1];
    const t2 = this.state.grid[r2][c2];

    // Combo check
    if (t1.special && t2.special) {
      this.processing = true;
      try {
        await this._resolveCombo(t1.special, t2.special, r1, c1, r2, c2);
        this.state.moves--;
        this.state.moves_log.push({ from: [r1, c1], to: [r2, c2] });
        this._emit('movesChanged', this.state.moves);
      } finally {
        this.processing = false;
      }
      return;
    }

    // Animate swap
    this.processing = true;
    try {
      await this._raceTimeout(new Promise(resolve => this.renderer.animateSwap(r1, c1, r2, c2, resolve)));

      // Logical swap
      this.state._swap(r1, c1, r2, c2);

      const matches = this.state.findMatches();
      if (matches.length > 0) {
        this.state.moves--;
        this.state.moves_log.push({ from: [r1, c1], to: [r2, c2] });
        this._emit('movesChanged', this.state.moves);
        await this._processMatches();
      } else {
        // Swap back
        await this._raceTimeout(new Promise(resolve => this.renderer.animateSwap(r1, c1, r2, c2, resolve)));
        this.state._swap(r1, c1, r2, c2);
      }
    } finally {
      this.processing = false; // ввод отпускаем ВСЕГДА, даже если отрисовка упала
      try { this.repairSync(); } catch (e) { console.error('repairSync failed:', e); }
    }
  }

  async _processMatches() {
    this.state.cascadeCount = 0;
    let cascadeGuard = 0;
    while (cascadeGuard++ < 12) {
      const matches = this.state.findMatches();
      if (matches.length === 0) break;
      this.state.cascadeCount++;

      const specialForm = this.state.checkSpecialForm(matches);
      const center = matches[Math.floor(matches.length / 2)];

      // Score
      const base = matches.length >= 5 ? CONFIG.BASE_SCORE_MATCH_5 :
                   matches.length === 4 ? CONFIG.BASE_SCORE_MATCH_4 :
                   CONFIG.BASE_SCORE_MATCH_3;
      const multiplier = 1 + (this.state.cascadeCount - 1) * CONFIG.CASCADE_BONUS;
      const pts = Math.floor(base * multiplier);
      this.state.score += pts;
      this._emit('scoreChanged', this.state.score, pts, this.state.cascadeCount);

      // Create special if applicable
      if (specialForm && matches.length >= 4) {
        const spTile = this.state.grid[center.r][center.c];
        spTile.special = specialForm;
        spTile.color = -1;
        // FIX: пересоздаём спрайт special tile
        const oldVis = spTile.visual;
        if (oldVis && oldVis.parent) oldVis.parent.removeChild(oldVis);
        const newVis = this.renderer._createTileSprite(spTile);
        spTile.visual = newVis;
        this.renderer.boardContainer.addChild(newVis);
        const others = matches.filter(m => !(m.r === center.r && m.c === center.c));
        await this._destroyTiles(others);
      } else {
        await this._destroyTiles(matches);
      }

      await this._applyGravity();
    }

    // Страховка: если после цикла остались пустые ячейки (напр. после комбо) — досыпаем
    let hasEmpty = false;
    for (let r = 0; r < CONFIG.GRID_SIZE && !hasEmpty; r++) {
      for (let c = 0; c < CONFIG.GRID_SIZE; c++) {
        if (this.state.grid[r][c].isEmpty()) { hasEmpty = true; break; }
      }
    }
    if (hasEmpty) await this._applyGravity();
    this.repairSync();

    if (!this.state.hasAnyMove()) {
      this._emit('noMoves');
      this.state.shuffle();
      this.renderer.renderGrid(this.state.grid);
    }

    if (this.state.score >= this.state.target && !this._completeSent) {
      this._completeSent = true;
      this._emit('levelComplete', this.state.score, this.state.moves);
    } else if (this.state.moves <= 0) {
      this._emit('levelFailed', this.state.score);
    }
  }

  async _destroyTiles(tiles) {
    const tilesWithVis = tiles.map(t => {
      const vis = this.renderer._getVisual(t.r, t.c);
      return { ...t, vis };
    });
    await this._raceTimeout(new Promise(resolve => this.renderer.animateDestroy(tilesWithVis, resolve)));
    for (const t of tiles) {
      this.state.grid[t.r][t.c] = new Tile(t.r, t.c, -1);
    }
  }

  async _applyGravity() {
    const moves = [];
    for (let c = 0; c < CONFIG.GRID_SIZE; c++) {
      let writeRow = CONFIG.GRID_SIZE - 1;
      for (let r = CONFIG.GRID_SIZE - 1; r >= 0; r--) {
        if (!this.state.grid[r][c].isEmpty()) {
          if (r !== writeRow) {
            moves.push({ from: { r, c }, to: { r: writeRow, c } });
            this.state.grid[writeRow][c] = this.state.grid[r][c];
            this.state.grid[writeRow][c].r = writeRow;
            this.state.grid[r][c] = new Tile(r, c, -1);
          }
          writeRow--;
        }
      }
      // Spawn new
      for (let r = writeRow; r >= 0; r--) {
        const colorCount = this.state.level >= 100 ? 6 : CONFIG.COLORS;
        let color;
        let guard = 0;
        do {
          color = Math.floor(Math.random() * colorCount);
          guard++;
        } while (guard < 50 && (
          (c >= 2 && this.state.grid[r][c-1].color === color && this.state.grid[r][c-2].color === color) ||
          (c <= CONFIG.GRID_SIZE - 3 && this.state.grid[r][c+1].color === color && this.state.grid[r][c+2].color === color) ||
          (r <= CONFIG.GRID_SIZE - 3 && this.state.grid[r+1][c].color === color && this.state.grid[r+2][c].color === color)
        ));
        const tile = new Tile(r, c, color);
        this.state.grid[r][c] = tile;
        const sprite = this.renderer._createTileSprite(tile);
        sprite.y = this.renderer.padding - (r + 1) * (this.renderer.tileSize + this.renderer.gap);
        tile.visual = sprite;
        this.renderer.boardContainer.addChild(sprite);
        moves.push({ from: { r: -1, c }, to: { r, c }, vis: sprite }); // spawn animation
      }
    }
    return this._raceTimeout(new Promise(resolve => this.renderer.animateFall(moves, resolve)));
  }

  async _resolveCombo(sp1, sp2, r1, c1, r2, c2) {
    const key = this.state.getComboKey(sp1, sp2);
    const combo = key ? COMBO_MATRIX[key] : null;
    const centerR = Math.floor((r1 + r2) / 2);
    const centerC = Math.floor((c1 + c2) / 2);

    this._emit('combo', sp1, sp2, combo?.effect || 'explosion');

    if (combo?.effect === 'clearAll') {
      const toDestroy = [];
      for (let r = 0; r < CONFIG.GRID_SIZE; r++) {
        for (let c = 0; c < CONFIG.GRID_SIZE; c++) {
          toDestroy.push({ r, c, color: this.state.grid[r][c].color });
        }
      }
      await this._destroyTiles(toDestroy);
      for (let r = 0; r < CONFIG.GRID_SIZE; r++) {
        for (let c = 0; c < CONFIG.GRID_SIZE; c++) {
          this.state.grid[r][c] = new Tile(r, c, Math.floor(Math.random() * CONFIG.COLORS));
        }
      }
      this.state.score += combo.score;
    } else if (combo?.effect === 'cross') {
      const toDestroy = [];
      for (let i = 0; i < CONFIG.GRID_SIZE; i++) {
        toDestroy.push({ r: centerR, c: i, color: this.state.grid[centerR][i].color });
        toDestroy.push({ r: i, c: centerC, color: this.state.grid[i][centerC].color });
      }
      await this._destroyTiles(toDestroy);
      for (let i = 0; i < CONFIG.GRID_SIZE; i++) {
        this.state.grid[centerR][i] = new Tile(centerR, i, Math.floor(Math.random() * CONFIG.COLORS));
        this.state.grid[i][centerC] = new Tile(i, centerC, Math.floor(Math.random() * CONFIG.COLORS));
      }
      this.state.score += combo.score;
    } else {
      // Default: 5x5 explosion
      const toDestroy = [];
      for (let r = Math.max(0, centerR - 2); r <= Math.min(CONFIG.GRID_SIZE - 1, centerR + 2); r++) {
        for (let c = Math.max(0, centerC - 2); c <= Math.min(CONFIG.GRID_SIZE - 1, centerC + 2); c++) {
          toDestroy.push({ r, c, color: this.state.grid[r][c].color });
        }
      }
      await this._destroyTiles(toDestroy);
      for (let r = Math.max(0, centerR - 2); r <= Math.min(CONFIG.GRID_SIZE - 1, centerR + 2); r++) {
        for (let c = Math.max(0, centerC - 2); c <= Math.min(CONFIG.GRID_SIZE - 1, centerC + 2); c++) {
          this.state.grid[r][c] = new Tile(r, c, Math.floor(Math.random() * CONFIG.COLORS));
        }
      }
      this.state.score += combo?.score || 600;
    }

    this._emit('scoreChanged', this.state.score);
    await this._processMatches();
  }

  useBooster(type, targetR, targetC) {
    if (this.processing) return;
    // Implement booster logic
  }

  shuffle() {
    this.state.shuffle();
    this.renderer.renderGrid(this.state.grid);
  }

  destroy() {
    this.renderer.destroy();
  }

  // Досыпка пустых ячеек в state (без анимаций) — для сторожа
  _fillStateHoles() {
    const GR = CONFIG.GRID_SIZE;
    for (let c = 0; c < GR; c++) {
      let writeRow = GR - 1;
      for (let r = GR - 1; r >= 0; r--) {
        const t = this.state.grid[r][c];
        if (!(t.color === -1 && !t.special)) {
          if (r !== writeRow) {
            this.state.grid[writeRow][c] = t;
            t.r = writeRow;
            this.state.grid[r][c] = new Tile(r, c, -1);
          }
          writeRow--;
        }
      }
      const colorCount = this.state.level >= 100 ? 6 : CONFIG.COLORS;
      for (let r = writeRow; r >= 0; r--) {
        let color, guard = 0;
        do {
          color = Math.floor(Math.random() * colorCount);
          guard++;
        } while (guard < 50 && (
          (c >= 2 && this.state.grid[r][c-1].color === color && this.state.grid[r][c-2].color === color) ||
          (r <= GR - 3 && this.state.grid[r+1][c].color === color && this.state.grid[r+2][c].color === color)
        ));
        this.state.grid[r][c] = new Tile(r, c, color);
      }
    }
  }

  // Единственный источник истины: синхронизирует спрайты с state
  _syncVisuals() {
    const GR = CONFIG.GRID_SIZE;
    const pad = this.renderer.padding;
    const ts = this.renderer.tileSize;
    const gap = this.renderer.gap;
    const cellSize = ts + gap;
    const existing = new Map();
    for (const ch of [...this.renderer.boardContainer.children]) {
      const c = Math.round((ch.x - pad) / cellSize);
      const r = Math.round((ch.y - pad) / cellSize);
      if (r >= 0 && r < GR && c >= 0 && c < GR) {
        const key = `${r},${c}`;
        if (!existing.has(key)) existing.set(key, ch);
        else this.renderer.boardContainer.removeChild(ch);
      } else {
        this.renderer.boardContainer.removeChild(ch);
      }
    }
    for (let r = 0; r < GR; r++) {
      for (let c = 0; c < GR; c++) {
        const t = this.state.grid[r][c];
        const key = `${r},${c}`;
        const isEmpty = (t.color === -1 && !t.special);
        const sprite = existing.get(key);
        if (isEmpty) {
          if (sprite) this.renderer.boardContainer.removeChild(sprite);
        } else {
          if (sprite) {
            const txt = sprite.children.find(ch => ch.text !== undefined);
            if (txt) txt.text = t.special ? '\u2b50' : COLOR_DEFS[t.color]?.emoji || '';
            t.visual = sprite;
          } else {
            const sp = this.renderer._createTileSprite(t);
            this.renderer.boardContainer.addChild(sp);
            t.visual = sp;
          }
        }
      }
    }
  }

  // Сторожевой таймаут: не даём анимации зависнуть (фоновая вкладка, ошибка rAF)
  _raceTimeout(promise, ms = 800) {
    return Promise.race([
      promise,
      new Promise(resolve => setTimeout(() => {
        console.warn('engine: animation timeout, force-continue');
        resolve();
      }, ms))
    ]);
  }

  _emit(event, ...data) {
    if (this.eventCallback) this.eventCallback(event, ...data);
  }

  // Авто-лечение рассинхрона: спрайт привязывается к ячейке по ФАКТИЧЕСКОЙ позиции
  // (state._swap меняет только color/special, поэтому tile.visual ненадёжен — источник истины: координаты спрайта)
  repairSync() {
    // Полная перерисовка поля из state — единый источник истины.
    // Убирает любые рассинхроны спрайтов/цветов после свайпов, каскадов и комбо.
    this.renderer.boardContainer.removeChildren();
    for (let r = 0; r < CONFIG.GRID_SIZE; r++) {
      for (let c = 0; c < CONFIG.GRID_SIZE; c++) {
        const t = this.state.grid[r][c];
        if (t.isEmpty()) { t.visual = null; continue; }
        const sprite = this.renderer._createTileSprite(t);
        t.visual = sprite;
        this.renderer.boardContainer.addChild(sprite);
      }
    }
  }

  // Диагностика: сверка state.grid с визуальными спрайтами
  checkSync() {
    const bad = [];
    for (let r = 0; r < CONFIG.GRID_SIZE; r++) {
      for (let c = 0; c < CONFIG.GRID_SIZE; c++) {
        const t = this.state.grid[r][c];
        if (!t.isEmpty() && (!t.visual || t.visual.parent !== this.renderer.boardContainer)) {
          bad.push(['no-visual', r, c, t.color]);
        }
      }
    }
    const seen = new Set();
    for (const child of this.renderer.boardContainer.children) {
      const tx = Math.round((child.x - this.renderer.padding) / (this.renderer.tileSize + this.renderer.gap));
      const ty = Math.round((child.y - this.renderer.padding) / (this.renderer.tileSize + this.renderer.gap));
      if (tx < 0 || tx > 8 || ty < 0 || ty > 8) { bad.push(['orphan', ty, tx]); continue; }
      const key = ty + ',' + tx;
      if (seen.has(key)) bad.push(['dup-sprite', ty, tx]);
      seen.add(key);
    }
    return bad;
  }
}

export default Match3Engine;
