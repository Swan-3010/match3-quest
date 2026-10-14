# Match Quest — контекст проекта
## Стек
Laravel 12 (skeleton без app/Providers; в config/ только game.php, остальное — дефолты фреймворка), PHP 8.2 (prod/CI platform 8.2.33; локально может быть 8.5 — это НЕ полная проверка), SQLite локально / MySQL на проде, Sanctum, API /api/v1 (apiPrefix в bootstrap/app.php). tinker не установлен.
## Фронтенд
PixiJS 7 (зависимость в package.json), но игровой код — plain-скрипты из public/assets/js/ (app.js, auth.js, api-client.js, game_engine_pixijs.js) с версионным query (?v=YYYYMMDDx). При правке JS — бампать версию, иначе браузеры держат старый кэш. Vite собирает пустой stub из resources/js (42 байта) — не путать с игрой. sw.js инертный (оффлайн-кэш отключён). window.API_BASE из meta api-base в resources/views/layouts/app.blade.php. Прод опубликован под /3Row/ (см. runbook).
## Ключевые решения
- Единый источник истины: state поля в одном объекте, после каждого хода — полная перерисовка (не точечные правки спрайтов).
- Баги UI ищем в public/assets/js/game_engine_pixijs.js (порядок: state → renderer → input). На main движок разделён: Match3Engine (state) + GameRenderer (app, tileSize, padding, gap).
## Как работаем
- Изменения — командами/патчами, без ручного редактирования файлов мной.
- После каждого шага — короткий отчёт: что сделано, как проверить.
- Перед рискованными правками — сначала план.
- Деплой/прод-задачи: читать docs/BEGET_DEPLOY_RUNBOOK.md (ветка рабочая схема: PR в main → Actions деплоит на Beget; merge и любая запись в production — только с согласия владельца).
## Окружение
WSL2 Ubuntu. Фронт локально: php artisan serve.
Тесты: `vendor/bin/phpunit` (команды `php artisan test` нет). Сьюит жив: tests/TestCase.php, factories в database/factories/ (UserFactory, LevelFactory), фиче-тесты на /api/v1/*. Тесты ловят реальные баги — напр. рег жизней в LifeRegenService зависит от направления diffInMinutes (Carbon 3 возвращает знаковую разницу).
Production: https://lab.iig.ru/3Row/. Актуальные баги — docs/BUGS.md.
