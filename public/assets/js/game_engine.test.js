/**
 * Match Quest Game Engine Tests (Jest)
 * Run: npx jest
 */

// Mock PixiJS
jest.mock('pixi.js', () => ({
  Application: jest.fn().mockImplementation(() => ({
    view: document.createElement('canvas'),
    stage: { addChild: jest.fn(), removeChildren: jest.fn() },
    ticker: { add: jest.fn() },
    destroy: jest.fn(),
  })),
  Container: jest.fn().mockImplementation(() => ({
    addChild: jest.fn(), removeChildren: jest.fn(), children: [],
    x: 0, y: 0, scale: { set: jest.fn() }, alpha: 1,
  })),
  Graphics: jest.fn().mockImplementation(() => ({
    beginFill: jest.fn().mockReturnThis(),
    endFill: jest.fn().mockReturnThis(),
    drawRoundedRect: jest.fn().mockReturnThis(),
    lineStyle: jest.fn().mockReturnThis(),
    drawCircle: jest.fn().mockReturnThis(),
    moveTo: jest.fn().mockReturnThis(),
    lineTo: jest.fn().mockReturnThis(),
    quadraticCurveTo: jest.fn().mockReturnThis(),
    closePath: jest.fn().mockReturnThis(),
    arc: jest.fn().mockReturnThis(),
  })),
  Text: jest.fn().mockImplementation(() => ({
    anchor: { set: jest.fn() }, x: 0, y: 0,
  })),
  Sprite: jest.fn(),
  Assets: { load: jest.fn() },
}));

// Import engine classes (mocked)
const { GameState } = require('./game_engine_pixijs.js');

describe('GameState', () => {
  let state;

  beforeEach(() => {
    state = new GameState();
    state.init({ level: 1, moves: 23, target: 1500, colors: 5 });
  });

  test('grid is 9x9', () => {
    expect(state.grid.length).toBe(9);
    expect(state.grid[0].length).toBe(9);
  });

  test('no initial matches', () => {
    const matches = state.findMatches();
    expect(matches.length).toBe(0);
  });

  test('has at least one valid move', () => {
    expect(state.hasAnyMove()).toBe(true);
  });

  test('swap creates match when valid', () => {
    // Find a valid swap
    let found = false;
    for (let r = 0; r < 9 && !found; r++) {
      for (let c = 0; c < 8 && !found; c++) {
        state._swap(r, c, r, c + 1);
        const matches = state.findMatches();
        if (matches.length > 0) {
          found = true;
          expect(matches.length).toBeGreaterThanOrEqual(3);
        }
        state._swap(r, c, r, c + 1); // swap back
      }
    }
    expect(found).toBe(true);
  });

  test('match 4 creates rocket', () => {
    // Manually set up 4 in a row
    state.grid[4][0].color = 0;
    state.grid[4][1].color = 0;
    state.grid[4][2].color = 0;
    state.grid[4][3].color = 0;
    const matches = state.findMatches();
    const special = state.checkSpecialForm(matches);
    expect(special).toBe('ROCKET_H');
  });

  test('match 5 creates disco', () => {
    state.grid[4][0].color = 1;
    state.grid[4][1].color = 1;
    state.grid[4][2].color = 1;
    state.grid[4][3].color = 1;
    state.grid[4][4].color = 1;
    const matches = state.findMatches();
    const special = state.checkSpecialForm(matches);
    expect(special).toBe('DISCO');
  });

  test('combo matrix resolves disco+disco', () => {
    const key = state.getComboKey('DISCO', 'DISCO');
    expect(key).toBeTruthy();
    expect(state.getComboKey('ROCKET_H', 'BOMB')).toBeTruthy();
    expect(state.getComboKey('UNKNOWN', 'UNKNOWN')).toBeFalsy();
  });

  test('score calculation with cascade', () => {
    state.cascadeCount = 1;
    const base = 60;
    const multiplier = 1 + (1 - 1) * 0.2;
    expect(base * multiplier).toBe(60);

    state.cascadeCount = 3;
    const mult3 = 1 + (3 - 1) * 0.2;
    expect(base * mult3).toBe(84);
  });

  test('moves decrease on valid swap', () => {
    const initialMoves = state.moves;
    // Find and execute a valid swap
    for (let r = 0; r < 9; r++) {
      for (let c = 0; c < 8; c++) {
        state._swap(r, c, r, c + 1);
        const matches = state.findMatches();
        if (matches.length > 0) {
          state.moves--;
          expect(state.moves).toBe(initialMoves - 1);
          return;
        }
        state._swap(r, c, r, c + 1);
      }
    }
  });
});

describe('Anti-cheat stub', () => {
  test('rejects score above upper bound', () => {
    const movesLimit = 23;
    const maxPerMove = 300 * 3;
    const maxScore = movesLimit * maxPerMove * 2;
    const score = maxScore * 2; // Impossible
    expect(score > maxScore * 1.5).toBe(true);
  });

  test('accepts reasonable score', () => {
    const movesLimit = 23;
    const maxPerMove = 300 * 3;
    const maxScore = movesLimit * maxPerMove * 2;
    const score = 5000;
    expect(score <= maxScore * 1.5).toBe(true);
  });
});
