# Match Quest 🎮

**Match-3 игра с бесконечной прогрессией уровней**

Поле 9×9, 500+ уровней, спецфишки, комбо-матрица, магазин, ежедневные бонусы, PWA.

---

## 🚀 Быстрый старт

```bash
# 1. Клонировать
git clone git@github.com:Swan-3010/match3-quest.git
cd match3-quest

# 2. Установить зависимости
composer install
npm ci

# 3. Настроить окружение
cp .env.example .env
php artisan key:generate
# Отредактировать .env (DB, MAIL)

# 4. База данных
php artisan migrate --seed
php artisan levels:generate 500

# 5. Сборка
npm run build

# 6. Запуск
php artisan serve
```

---

## 📁 Структура проекта

```
match3-quest/
├── .github/workflows/deploy.yml    # CI/CD на Beget
├── app/
│   ├── Http/Controllers/
│   │   ├── Api/                    # 8 API контроллеров
│   │   └── Admin/                  # 7 админ-контроллеров
│   ├── Models/                     # 18 Eloquent моделей
│   ├── Services/
│   │   ├── Game/AntiCheatService.php
│   │   └── User/LifeRegenService.php
│   └── Console/Commands/GenerateLevels.php
├── config/game.php                 # Игровые константы
├── database/
│   ├── migrations/                 # 18 таблиц
│   └── seeders/                    # 8 сидеров
├── public/
│   ├── .htaccess                   # Apache rewrite rules
│   ├── manifest.json               # PWA manifest
│   ├── sw.js                       # Service Worker
│   └── assets/
│       ├── js/
│       │   ├── auth.js             # JWT management
│       │   ├── api-client.js       # Fetch + offline queue
│       │   ├── app.js              # SPA router
│       │   ├── game_engine_pixijs.js  # PixiJS v7 движок
│       │   └── game_engine.test.js    # Jest тесты
│       └── css/app.css
├── resources/views/
│   ├── layouts/app.blade.php       # Базовый layout
│   ├── index.blade.php             # SPA (7 экранов)
│   └── admin/                      # 12 Blade-шаблонов админки
├── routes/
│   ├── api.php                     # 18 API endpoints
│   ├── web.php                     # Web routes
│   └── admin.php                   # Admin routes
├── tests/
│   ├── Feature/                    # 4 feature теста
│   └── Unit/                       # 3 unit теста
├── .env.beget                      # Production config
├── deploy.sh                       # Manual deploy script
├── load_test.php                   # Load testing
└── README.md                       # Этот файл
```

---

## 🔗 API Endpoints

### Auth
| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/auth/register` | Регистрация |
| POST | `/api/auth/login` | Вход |
| POST | `/api/auth/logout` | Выход |
| POST | `/api/auth/forgot-password` | Сброс пароля |

### Game
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/levels` | Список уровней |
| POST | `/api/levels/<built-in function id>/start` | Начало уровня |
| POST | `/api/levels/<built-in function id>/complete` | Завершение уровня |

### Shop & Daily
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/shop/items` | Товары магазина |
| POST | `/api/shop/purchase` | Покупка |
| GET | `/api/daily/status` | Статус ежедневного бонуса |
| POST | `/api/daily/claim` | Получить бонус |

---

## 🧪 Тестирование

```bash
# Backend (PHPUnit)
php artisan test

# Frontend (Jest)
npx jest

# Нагрузочный тест
php load_test.php https://lab.iig.ru 10 100
```

---

## 🚀 Деплой на Beget

См. [BEGET_DEPLOY.md](BEGET_DEPLOY.md) — пошаговая инструкция.

Кратко:
1. Настроить GitHub Secrets (SSH ключ, хост, путь)
2. Push в `main` → автоматический деплой
3. Или `./deploy.sh` для ручного деплоя

---

## 📊 Статистика проекта

- **Файлов:** 109
- **Размер:** 214 KB
- **Миграций:** 18
- **Моделей:** 18
- **API endpoints:** 18
- **Blade-шаблонов:** 14
- **JS-модулей:** 4
- **PHPUnit тестов:** 7
- **Jest тестов:** 1
- **Фаз разработки:** 10

---

## 🛡️ Лицензия

MIT License. Разработано в режиме Work с Kimi AI.
