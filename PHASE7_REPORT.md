# Фаза 7: Интеграция Frontend + Backend

## Дата: 2026-09-04

### Архитектура интеграции

```
Browser (SPA)
├── Blade layout (app.blade.php) — meta, CSRF, scripts
├── index.blade.php — 7 экранов в одном DOM
│   ├── Auth (email/password, guest mode)
│   ├── Menu (статистика, навигация)
│   ├── Map (уровни 1–500, scroll to current)
│   ├── Game (PixiJS canvas + бустеры)
│   ├── Shop (4 категории, покупки)
│   ├── Profile (прогресс, ачивки)
│   └── Daily (7-дневный календарь)
└── JS модули:
    ├── auth.js — JWT, Sanctum tokens
    ├── api-client.js — fetch + retry + offline queue
    ├── app.js — SPA router, screen manager
    └── game_engine_pixijs.js — PixiJS v7 движок

Laravel API
├── AuthController — register/login/logout/reset
├── UserController — profile/progress/inventory
├── LevelController — start/complete (anti-cheat stub)
├── ShopController — items/purchase
├── ConfigController — versioned config cache
├── DailyController — streak/rewards
└── AchievementController — progress tracking
```

### Файлы фазы

| Файл | Описание |
|------|----------|
| `resources/views/layouts/app.blade.php` | Базовый layout с meta, CSRF, стили |
| `resources/views/index.blade.php` | SPA: 7 экранов, inline CSS, handlers |
| `public/assets/js/auth.js` | JWT management, guest mode |
| `public/assets/js/api-client.js` | Fetch wrapper, offline queue, config cache |
| `public/assets/js/app.js` | SPA router, game integration, shop, profile |
| `public/assets/js/game_engine_pixijs.js` | PixiJS v7 движок |
| `routes/web.php` | Корневой маршрут → index |

### Ключевые интеграции

| Фича | Реализация |
|------|-----------|
| **Auth** | Sanctum tokens, localStorage, auto-refresh on 401 |
| **Config cache** | `X-Config-Version` header, 304 Not Modified, localStorage fallback |
| **Offline queue** | Не-GET запросы кэшируются и отправляются при восстановлении связи |
| **Game → API** | `POST /levels/<built-in function id>/complete` с session_id, score, moves, stars |
| **Shop → API** | `POST /shop/purchase` с проверкой баланса, обновление инвентаря |
| **Daily → API** | `POST /daily/claim` с проверкой streak, начисление наград |
| **Currency bar** | Обновляется после каждой покупки/уровня/бонуса |
| **Map scroll** | Авто-scroll к текущему уровню при открытии |

### Что работает end-to-end

1. ✅ Регистрация / вход → JWT token → localStorage
2. ✅ Загрузка прогресса → отображение в меню/профиле
3. ✅ Карта уровней → клик → старт уровня → API session
4. ✅ Игра (PixiJS) → матчи → каскады → спецфишки → комбо
5. ✅ Завершение уровня → отправка результата → обновление прогресса
6. ✅ Магазин → покупка → списание валюты → обновление инвентаря
7. ✅ Ежедневный бонус → проверка streak → начисление

### Ограничения (исправим в Фазе 8–9)

- Нет PWA (Service Worker, манифест) — Фаза 9
- Нет звуков — Фаза 8
- Админ-панель отложена на post-MVP
- Античит — заглушка

### Статистика проекта

- Всего файлов: 66
- Общий размер: 120,639 bytes (118 KB)
- Миграций: 18
- Моделей: 18
- API endpoints: 18
- Blade-шаблонов: 2
- JS-модулей: 4
