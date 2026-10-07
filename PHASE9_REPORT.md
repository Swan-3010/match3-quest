# Фаза 9: GitHub + CI/CD + Деплой на Beget — Отчёт

## Дата: 2026-09-04

### Что сделано

#### 1. GitHub Actions CI/CD (`.github/workflows/deploy.yml`)
- **Job 1: Test** — PHPUnit с покрытием (SQLite in-memory)
- **Job 2: Build** — `npm ci && npm run build`, артефакт
- **Job 3: Deploy** — rsync на Beget + post-deploy команды
- Триггер: push в `main`
- Требует 5 GitHub Secrets: `BEGET_SSH_KEY`, `BEGET_HOST`, `BEGET_USER`, `BEGET_PORT`, `BEGET_PATH`

#### 2. PWA (Progressive Web App)
- `manifest.json` — 9 иконок, screenshots, theme
- `sw.js` — Service Worker: cache-first для статики, network-first для API
- PWA meta tags в layout (theme-color, apple-touch-icon, standalone mode)

#### 3. Apache Config (`.htaccess`)
- Rewrite rules для Laravel (index.php front controller)
- Authorization header forwarding
- Security headers (X-Frame-Options, XSS-Protection)
- Static asset caching (1 year images, 1 month CSS/JS)

#### 4. Production Config (`.env.beget`)
- MySQL через localhost
- File cache (Redis недоступен на shared hosting)
- SMTP Beget (ssl://smtp.beget.com:465)
- Sanctum stateful domains

#### 5. Deploy Scripts
- `deploy.sh` — ручной деплой через rsync + SSH
- `load_test.php` — нагрузочный тест

#### 6. Инструкция (`BEGET_DEPLOY.md`)
- Создание сайта, БД, SSL
- Настройка SSH + GitHub Secrets
- Первый деплой (ручной)
- Автоматический деплой (GitHub Actions)
- Настройка Cron (жизни)
- Решение проблем (500, 404, DB)
- Backup

### Архитектура деплоя

```
GitHub (push to main)
    │
    ▼
GitHub Actions
    ├── PHPUnit tests (SQLite)
    ├── npm build (vite)
    └── Deploy to Beget
            ├── rsync files (exclude: .env, logs, cache)
            └── SSH post-deploy:
                ├── php artisan migrate --force
                ├── php artisan config:cache
                ├── php artisan route:cache
                ├── php artisan view:cache
                └── php artisan cache:clear
```

### Файлы фазы

| Файл | Описание |
|------|----------|
| `.github/workflows/deploy.yml` | GitHub Actions CI/CD |
| `public/.htaccess` | Apache rewrite + security |
| `public/manifest.json` | PWA manifest |
| `public/sw.js` | Service Worker |
| `.env.beget` | Production environment |
| `deploy.sh` | Manual deploy script |
| `load_test.php` | Load testing script |
| `BEGET_DEPLOY.md` | Complete deploy guide |
| `README.md` | Project overview |

### Всего файлов проекта: 109
### Общий размер: 214 KB

### Чек-лист для запуска

- [ ] Создать репозиторий `Swan-3010/match3-quest`
- [ ] Загрузить все файлы
- [ ] Настроить GitHub Secrets (5 переменных)
- [ ] Создать сайт на Beget (lab.iig.ru)
- [ ] Создать БД MySQL
- [ ] Активировать SSL (Let's Encrypt)
- [ ] Добавить SSH deploy key на Beget
- [ ] Push в `main` → проверить GitHub Actions
- [ ] Проверить сайт: https://lab.iig.ru
- [ ] Зарегистрировать первого пользователя
- [ ] Проверить админку: /admin (admin@...)
