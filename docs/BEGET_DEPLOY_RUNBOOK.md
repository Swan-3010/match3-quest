# Match3 Quest: разработка и автоматический деплой на Beget

Дата фиксации состояния: 13 октября 2026 года.
Документ для владельца проекта и KimiCode. Не содержит приватных ключей и паролей.

## 1. Быстрый контекст для KimiCode

Проект: https://github.com/Swan-3010/match3-quest
Production: https://lab.iig.ru/3Row/
Ветка публикации: main.
Workflow: .github/workflows/deploy.yml, имя «Deploy Match3 Quest to Beget».

Подтверждённое состояние:
- Повторный ручной запуск GitHub Actions завершился успешно; job deploy зелёный.
- manifest.json, /up и главная страница под /3Row/ вернули HTTP 200.
- Пользователь подтвердил открытие базовой страницы.
- Полная функциональность игры, начальные данные, cron и фоновые задачи НЕ подтверждены.
- На Beget настроен общий доступ к внешнему каталогу приложения; именно изоляция сайтов ранее мешала веб-доступу.

Задача агента: продолжать разработку и отладку, сохраняя рабочий автодеплой и публикацию именно в /3Row/. Не повторять первоначальную настройку, не переезжать в корень домена, не менять SSH/БД без причины.

## 2. Реальные реквизиты и пути

| Назначение | Значение |
|---|---|
| SSH host | adolmax0.beget.tech |
| SSH port | 22 |
| Основной SSH user для деплоя | adolmax0 |
| Имя сервера в приветствии | geist |
| Домашняя папка основного аккаунта | /home/a/adolmax0 |
| Закрытый относительно HTTP каталог приложения | /home/a/adolmax0/apps/match3-quest |
| Публичная папка Laravel | /home/a/adolmax0/apps/match3-quest/public |
| Путь публикации | /home/a/adolmax0/lab.iig.ru/public_html/3Row |
| URL | https://lab.iig.ru/3Row/ |
| Проверка Laravel | https://lab.iig.ru/3Row/up |
| MySQL host | localhost |
| MySQL port | 3306 |
| Database / DB username | adolmax0_3row |
| PHP CLI на Beget | php8.2, /usr/local/bin/php8.2 |
| PHP веб-сайта в панели | 8.2 |
| rsync на сервере | /usr/bin/rsync |
| Приватный SSH-ключ в локальном WSL | ~/.ssh/id_ed25519 |

Регистр важен: папка 3Row, база adolmax0_3row. Имя базы не содержит прописную R.

Схема:

```text
/home/a/adolmax0/apps/match3-quest/
  app/ bootstrap/ config/ database/ resources/ routes/
  vendor/ storage/ artisan composer.json composer.lock .env
  public/
    index.php .htaccess manifest.json sw.js assets/ build/

/home/a/adolmax0/lab.iig.ru/public_html/3Row
  -> /home/a/adolmax0/apps/match3-quest/public
```

match3-quest — каталог приложения, названный по репозиторию, а НЕ отдельный сайт в панели.
3Row — символьная ссылка на public. Код и .env не должны находиться внутри публичной папки.
Не трогать public_html/index.php, roman-provinces, cgi-bin и соседние проекты.

Дополнительный аккаунт adolmax0_lab имеет HOME=/home/a/adolmax0/lab.iig.ru/public_html и не мог создавать apps. Его ключ тоже был настроен, но workflow использует основной аккаунт adolmax0.
Никогда не использовать begetguide.beget.tech: это был ошибочно скопированный демонстрационный адрес.

## 3. Локальное окружение и две рабочие папки

WSL на Windows. Локально проверены PHP 8.5.4, Composer 2.9.5, Node 24.18.0, npm 11.16.0.
На Beget проверен PHP CLI 8.2.33; runner ранее показывал 8.2.34. Нельзя считать локальный тест на PHP 8.5 полноценным тестом PHP 8.2.

### Исходная рабочая папка

~/projects/match3-quest

В начале настройки в main были незакоммиченные изменения:
- app/Models/Level.php;
- app/Services/User/LifeRegenService.php;
- несколько Feature/Unit-тестов;
- новые tests/TestCase.php, database/factories/, AGENTS.md, docs/;
- изменённые/новые runtime-файлы storage и .phpunit.result.cache.

Эти изменения НЕ переносились автоматически в чистую папку настройки и НЕ считались опубликованными. Текущее состояние обязательно перечитать через git status/diff.
Не делать здесь git reset --hard, git clean -fd или принудительное переключение ветки.
Не использовать git add . без проверки: исторически runtime-файлы storage уже отслеживались Git.

### Папка, использованная для настройки

~/projects/match3-quest-deploy

Создана через git worktree от origin/main. Использовались ветки setup/beget-deploy и fix/composer-php82. Обе настройки были перенесены в main, но локальная активная ветка может остаться прежней.

Проверить перед любой работой:

```bash
cd "$HOME/projects/match3-quest-deploy"
git status --short
git branch --show-current
git worktree list
git fetch origin
git log --oneline -5 origin/main
```

Если есть незакоммиченные изменения — сначала согласовать их сохранение. Не уничтожать их ради синхронизации с main.

## 4. Текущие зависимости и решения совместимости

- Laravel в проверенном lock-файле: 12.69.3.
- league/commonmark: 2.10.3.
- Symfony несовместимых версий 8.1 заменён совместимыми версиями 7.4.
- composer.json: laravel/framework ^12.69; config.platform.php = 8.2.33.
- composer.lock и package-lock.json отслеживаются Git.
- Vite: 5.4.21 при последней сборке.
- laravel/sanctum: ограничение ^4.0 в ранее проверенном composer.json; точную текущую версию читать из lock-файла.
- Production Composer audit на момент настройки не обнаруживал известных advisory. Это не гарантия отсутствия будущих предупреждений.

Не заменять composer install в CI на composer update.
Не применять --ignore-platform-reqs или npm audit fix --force для подавления ошибки.
config.platform.php задаёт платформу разрешения зависимостей, но не меняет фактический интерпретатор PHP.

Обновление Composer-зависимостей делать отдельно в ветке, сохраняя platform.php:

```bash
composer config platform.php
composer update ИМЕНА_НУЖНЫХ_ПАКЕТОВ --with-all-dependencies --no-install --no-scripts
composer validate
composer audit --locked --no-dev
git diff -- composer.json
git diff --stat
```

Если появились конфликты/уязвимости — анализировать пакеты и ограничения, а не отключать проверки.
Изменение основной версии фреймворка требует проверки кода по upgrade guide.

## 5. GitHub Secrets

Repository Settings → Secrets and variables → Actions → Repository secrets.

| Secret | Назначение |
|---|---|
| BEGET_HOST | adolmax0.beget.tech |
| BEGET_USER | adolmax0 |
| BEGET_PORT | 22 |
| BEGET_SSH_KEY | Полное содержимое приватного ~/.ssh/id_ed25519, НЕ путь и НЕ .pub |
| BEGET_KNOWN_HOSTS | Полные записи known_hosts для сервера, НЕ отдельные SHA256 fingerprints |

Все пять сохранены владельцем и использованы успешным запуском.
BEGET_PATH текущему workflow не нужен: APP_DIR и WEB_DIR заданы прямо в YAML.
Не выводить содержимое Secrets или приватного ключа. Не коммитить их в AGENTS.md, .env, docs или workflow.

BEGET_KNOWN_HOSTS получен из локального ранее принятого known_hosts:

```bash
ssh-keygen -F adolmax0.beget.tech -f "$HOME/.ssh/known_hosts" \
  | grep -v '^#' > "$HOME/beget-known-hosts"
ssh-keygen -lf "$HOME/beget-known-hosts"
```

Сохранённые отпечатки на дату настройки:
- ED25519: SHA256:fuKjHKx2RrSYpZr6O5aS5zVLwoyZ5RO+7ZqYdd78XqI
- RSA: SHA256:E4k1cKTWGpC+L1PEehQ8z/zVkaejubjQYtcxrXpfLJQ

Независимая сверка с поддержкой не была подтверждена. При смене ключа сервера нельзя автоматически отключать StrictHostKeyChecking или слепо обновлять Secret: сначала подтвердить смену у Beget.

## 6. Серверный .env и база

Файл: /home/a/adolmax0/apps/match3-quest/.env.
Создан вручную на сервере. До общего доступа имел права 600; итоговые ACL после настройки общего доступа отдельно не фиксировались.
Основные несекретные параметры:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://lab.iig.ru/3Row
ASSET_URL=https://lab.iig.ru/3Row
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=adolmax0_3row
DB_USERNAME=adolmax0_3row
SESSION_DRIVER=file
QUEUE_CONNECTION=sync
```

Пароль БД и APP_KEY в документ не включены. Существующий APP_KEY сохранять, не генерировать заново при каждом деплое.
Предлагались CACHE_STORE=file и CACHE_DRIVER=file; используемый параметр определяется config/cache.php, его надо проверить перед изменением.

База успешно приняла пользователя adolmax0_3row@localhost. До первой миграции таблиц не было. После деплоя наличие игровых данных/сидеров не подтверждалось.
Удалённый MySQL-доступ с GitHub не нужен: миграции выполняются на Beget через SSH.

После изменения серверного .env пересоздать конфигурационный кэш:

```bash
cd /home/a/adolmax0/apps/match3-quest
php8.2 artisan config:clear
php8.2 artisan config:cache
```

Не включать APP_DEBUG=true публично. Не показывать cat .env в чатах/логах.
Исторически в репозитории был .env.beget. Его удаление из истории и ротация возможных секретов НЕ подтверждены; проверить локально без публикации содержимого. Исключение .env* из rsync не устраняет секрет из Git-истории.

## 7. Изоляция Beget: обязательное условие публикации

Поддержка подтвердила:
- сайты изолированы;
- веб-процессы не получают автоматически доступ к каталогам вне сайта;
- ACL управляются автоматически, менять их через setfacl нельзя;
- для внешнего каталога нужен «Общий доступ» через файловый менеджер.

Ранее происходило:
- /3Row/manifest.json и /3Row/up возвращали 502;
- /3Row/index.php возвращал 500;
- Apache: AH01512: mod_mime_magic: can't read .../3Row/index.php;
- setfacl возвращал Operation not permitted, exit code 1; изменения ACL НЕ применялись.

Решение, после которого получены HTTP 200 и зелёный повторный деплой:
1. Основной аккаунт adolmax0 → файловый менеджер.
2. Перейти ВНУТРЬ apps/match3-quest (не оставаться в apps и не открывать доступ ко всему HOME).
3. Верхнее меню «Инструменты» → «Настроить общий доступ к текущей директории».
4. По инструкции поддержки: «Чтение и запись», «Включая вложенные папки», «Открыть доступ».

Пункт расположен не в меню правой кнопки папки. «Изменить атрибуты» — не замена.

Безопасность: общий доступ ослабляет изоляцию от других сайтов аккаунта. Код вне public защищён от прямого HTTP-размещения, но не обязательно от чтения другим изолированным сайтом после открытия общего доступа.
Уточнить у Beget минимальную схему: чтение приложения и запись только storage/bootstrap/cache, а также наследование доступа новыми файлами rsync.
Подтверждено, что повторный деплой существующих файлов работает. Наследование для любых новых директорий пока не доказано.
Не добавлять setfacl или chmod -R 777 в workflow.

## 8. Что реально делает текущий workflow

Файл перечитан из main при подготовке инструкции; Git blob SHA:
4d8ee3d7b22fdf5fe8224fb9b9d4e026d6818048.
Этот SHA идентифицирует файл workflow, НЕ коммит всего проекта.
Источником истины после дальнейших изменений является актуальный .github/workflows/deploy.yml.

Триггеры:

```yaml
on:
  push:
    branches: [main]
  workflow_dispatch:
```

Параметры: ubuntu-latest, timeout 25 min, concurrency group match3-beget-production, cancel-in-progress false, contents: read.
Actions сейчас: checkout@v4, setup-node@v4, shivammathur/setup-php@v2. Node для сборки 24, PHP runner 8.2.

Последовательность:
1. Checkout коммита запуска.
2. PHP 8.2, расширения mbstring/pdo_mysql/bcmath/gd, Composer.
3. composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader.
4. check-platform-reqs --no-dev, audit --locked --no-dev, artisan version и route:list.
5. Node 24; npm ci; npm run build с ASSET_URL=https://lab.iig.ru/3Row.
6. Проверка public/build/manifest.json и public/assets/js/api-client.js.
7. Временный SSH-ключ на runner, known_hosts, StrictHostKeyChecking yes, BatchMode yes.
8. Проверки пользователя adolmax0, каталогов, .env и цели ссылки.
9. rsync -rz --itemize-changes всего подготовленного проекта в APP_DIR.
10. Серверный platform_check.php, config:clear, route:clear; генерация APP_KEY только при пустом поддержанном формате; migrate --force; config:cache; view:cache; route:list.
11. storage:link, если ссылка ещё не существует.
12. Проверка существующей ссылки 3Row. При первом переключении старая обычная папка сохранялась в /home/a/adolmax0/3Row-before-deploy-ДАТА, затем создавалась ссылка.
13. curl --fail ... https://lab.iig.ru/3Row/up с повторами.
14. Удаление временного приватного ключа с runner через if: always().

rsync исключает:
.git/, .github/, node_modules/, tests/, .env*, .phpunit.result.cache,
storage/, bootstrap/cache/, public/hot, public/storage,
public/assets/js/game_engine.test.js.

Следствия:
- Серверный .env не обновляется из Git.
- storage сохраняется, а папки могут быть созданы серверным шагом.
- bootstrap/cache не передаётся; кэши строятся на сервере.
- public/hot удаляется на сервере, чтобы не использовать локальный Vite dev server.
- vendor передаётся из CI.
- Нет --delete: удалённые из Git файлы могут остаться на сервере, включая старые vendor-файлы. Удаление требует отдельного безопасного плана.
- Нет phpunit job и функциональных API-тестов; CLI boot/route checks — не замена тестам.
- Нет автоматического db:seed, генерации уровней, cron и отдельного queue worker.
- Нет автоматического rollback или атомарного переключения releases. Обновление идёт в действующий каталог.
- Красный health check НЕ отменяет уже выполненные миграции и загрузку файлов.
- curl следует редиректам и не проверяет содержимое/конечный URL: зелёный pipeline не доказывает все функции игры.

## 9. Обычный безопасный цикл разработки

### 9.1 Начать новую задачу с чистой ветки

В чистой папке настройки:

```bash
cd "$HOME/projects/match3-quest-deploy"
git status --short
git fetch origin
git switch -c feature/КРАТКОЕ-ИМЯ origin/main
```

Сначала заменить КРАТКОЕ-ИМЯ. Если статус не чистый или имя уже занято — остановиться, не использовать force.
Можно продолжать в этой папке или создать новый git worktree для задачи. Не переключать main в другой worktree принудительно.

Если нужно включить старые незакоммиченные изменения исходной папки, сначала изучить git diff и список новых файлов. Переносить только согласованные исходники/тесты; не переносить runtime-кэши/сессии, .env, vendor и секреты.

### 9.2 Проверки перед коммитом

```bash
composer validate
composer audit --locked --no-dev
npm ci
ASSET_URL='https://lab.iig.ru/3Row' npm run build
git diff --check
git status --short
```

Для JS, где применимо:

```bash
node --check public/assets/js/api-client.js
node --check public/assets/js/auth.js
node --check public/sw.js
```

Тесты запускать в отдельном локальном/test окружении, не против production MySQL.
При установленном dev vendor можно использовать vendor/bin/phpunit с проверенной конфигурацией phpunit.xml.
Наличие php artisan test не гарантировано: сначала проверить доступность команды, не добавлять пакеты вслепую.
При исправлении тестов предпочтительно вернуть отдельный CI job на PHP 8.2, от которого будет зависеть deploy.
Никогда не запускать migrate:fresh или RefreshDatabase-тесты с реквизитами production.

### 9.3 Коммит и отправка feature-ветки

```bash
git add ПЕРЕЧЕНЬ_СОГЛАСОВАННЫХ_ФАЙЛОВ
git diff --cached --stat
git diff --cached
git commit -m "Краткое описание изменения"
git push -u origin feature/КРАТКОЕ-ИМЯ
```

Не использовать placeholder-команды без замены. Локальный коммит сам по себе не публикует сайт.
Push feature-ветки также не запускает production deployment, поскольку trigger ограничен main.

### 9.4 PR и merge в браузере

1. GitHub → Swan-3010/match3-quest → Pull requests → New pull request.
2. base: main, compare: feature/КРАТКОЕ-ИМЯ.
3. Create pull request, описание изменений и рисков миграции.
4. Files changed: проверить исходники, lock-файлы и отсутствие секретов/runtime-файлов.
5. Перед destructive миграциями иметь проверенный backup и план восстановления.
6. Merge pull request → Confirm merge (Squash and merge допустим).
7. Merge обновляет main и автоматически запускает деплой.

Прямой push main технически тоже запускает workflow, но для обычной работы предпочтителен PR.
Агент не должен сам выполнять merge/production-запись без согласия владельца.

## 10. Ручной повторный запуск

GitHub → Actions → Deploy Match3 Quest to Beget → Run workflow → Branch main → Run workflow.
Это реально выполняет весь deployment, включая migrate --force, а не только curl.

Либо открыть старый запуск → Re-run jobs. Учесть: повтор старого запуска использует его прежний коммит; для свежего main выбирать Run workflow на main.
Не использовать повторный запуск в качестве способа исправить плохой lock-файл, отсутствующий secret или настройку изоляции.

## 11. Наблюдение за запуском

GitHub → Actions → последний запуск → deploy → раскрыть шаг.
Проверить, что запуск относится к ожидаемому коммиту main.

Имена основных шагов:
- Install PHP dependencies
- Check PHP platform and Laravel boot
- Build frontend
- Configure SSH
- Check server
- Upload application
- Prepare Laravel and publish in 3Row
- HTTP health check

При ошибке копировать название первого красного шага и последние 40–60 строк ПЕРЕД Process completed.
Annotations — только сводка, не полноценный лог причины.
Зелёный повторный job был подтверждён владельцем скриншотом; предупреждения Node/Ubuntu не мешали выполнению.

## 12. Проверки после каждого деплоя

Из локального WSL:

```bash
for url in \
  'https://lab.iig.ru/3Row/manifest.json' \
  'https://lab.iig.ru/3Row/up' \
  'https://lab.iig.ru/3Row/'
do
  printf '\n--- %s ---\n' "$url"
  curl --silent --show-error --location \
    --connect-timeout 10 --max-time 20 \
    --output /dev/null \
    --write-out 'HTTP=%{http_code} FINAL_URL=%{url_effective}\n' "$url"
done
```

Ожидаются 200 и правильные FINAL_URL. Следом в браузере проверить login/registration, игру, переходы, Network и Console.
Статический 200 и /up 200 не гарантируют работоспособность каждой DB-операции или наличие уровней.

## 13. SSH и диагностика

Обычный вход из локального WSL:

```bash
ssh -i "$HOME/.ssh/id_ed25519" adolmax0@adolmax0.beget.tech
```

Строгая проверка беспарольного ключа:

```bash
ssh -i "$HOME/.ssh/id_ed25519" -p 22 \
  -o IdentitiesOnly=yes -o IdentityAgent=none \
  -o BatchMode=yes -o PreferredAuthentications=publickey \
  -o ControlPath=none \
  adolmax0@adolmax0.beget.tech \
  'whoami; printf "HOME=%s\n" "$HOME"; pwd'
```

Все команды должны содержать обычный user@host, без Markdown [..](mailto:..) обёртки.
Предупреждение post-quantum — не ошибка входа; не отключать проверки ради него.

На сервере:

```bash
cd /home/a/adolmax0/apps/match3-quest
php8.2 artisan --version
php8.2 artisan route:list --path=api/v1
php8.2 artisan route:list --path=up
php8.2 artisan migrate:status
ls -lt storage/logs
tail -n 80 storage/logs/laravel.log
readlink -f /home/a/adolmax0/lab.iig.ru/public_html/3Row
```

Если laravel.log отсутствует, искать другой свежий log и Error log в панели Beget.

Проверка БД без пароля в командной строке:

```bash
mysql -h localhost -u adolmax0_3row -p adolmax0_3row \
  -e 'SELECT DATABASE(), CURRENT_USER(); SHOW TABLES;'
```

Пароль вводить после приглашения. Логи перед передачей агенту очистить от токенов и персональных данных.

## 14. Типичные ошибки и правильные действия

| Ошибка | Действие |
|---|---|
| Lock требует PHP 8.4+ | Проверить config.platform.php=8.2.33, пересчитать lock локально, PR; не повышать только runner PHP |
| Composer audit нашёл advisory | Анализ и точечное обновление; не отключать аудит |
| npm ci без lock | Добавить валидный package-lock.json, согласованный с package.json |
| Vite manifest not found | Проверить build.manifest='manifest.json' и public/build/manifest.json |
| Permission denied publickey | Проверить BEGET_USER=adolmax0 и соответствие приватного ключа; не менять БД |
| Host key verification failed | Подтвердить ключ сервера у Beget, затем обновлять known_hosts |
| Статика 502, index.php 500, AH01512 | Проверить общий доступ Beget, symlink и Error log; не исправлять setfacl |
| Только динамика 500 | Свежий Laravel/Error log, PHP web version, DB, права записи; не включать debug публично |
| API идёт в /api/v1 | Исправлять base URL /3Row/api/v1, не переносить приложение в корень |
| Игра пустая | Проверить таблицы/сидеры/генерацию уровней, не делать migrate:fresh |
| Старые файлы после удаления в Git | Помнить, что rsync без --delete; согласовать точечную очистку |

## 15. Ресурсы, API и PWA

API prefix в bootstrap/app.php: api/v1. Полный URL: /3Row/api/v1.
В layout есть meta api-base через url('/api/v1') и meta app-base.
ApiClient берёт window.API_BASE либо meta api-base, затем fallback /3Row/api/v1.
В auth.js переход на login строится относительно app-base.
Manifest: start_url и scope /3Row/, пути иконок /3Row/assets/..., отсутствующие локальные картинки были убраны из записей.
Service worker регистрируется с областью /3Row/.

Оффлайн-кэширование временно отключено: текущий sw.js выполняет skipWaiting/clients.claim без fetch interception.
Не возвращать старый worker, который удалял чужие cache names, кэшировал динамические ответы и ссылался на отсутствующие файлы.
Если PWA нужна, отдельно проектировать безопасное кэширование только собственных статических ресурсов с версионированием и проверкой новых билдов.

Vite inputs resources/js/app.js и resources/css/app.css содержат комментарии. Пустой app chunk ожидаем для этого состояния, а не признак отсутствия игры: игровой JS в public/assets/js, CSS inline в Blade.
Если архитектура фронтенда меняется — нужно осознанно менять импорты и публикацию, а не просто удалять «пустую» сборку.

## 16. Backup и восстановление

До рискованных миграций — backup БД через Beget или mysqldump с запросом пароля, хранить вне public_html, с ограниченным доступом и проверить восстановление.
Серверный .env и APP_KEY также должны иметь защищённый backup; ни один из них не публиковать в Git.

Пример backup БД, выполняемый НА СЕРВЕРЕ:

```bash
mkdir -p "$HOME/backups/match3-quest"
chmod 700 "$HOME/backups/match3-quest"
BACKUP="$HOME/backups/match3-quest/db-$(date +%Y%m%d-%H%M%S).sql"
(umask 077; mysqldump --single-transaction --no-tablespaces \
  -h localhost -u adolmax0_3row -p adolmax0_3row > "$BACKUP")
```

Проверить код завершения mysqldump и содержимое/размер backup. Непустой файл сам по себе не доказывает корректность dump. Для non-transactional tables single-transaction не даёт общей гарантии согласованности.
Если платформа не поддерживает параметры — остановиться и использовать подтверждённый механизм Beget, не считать частичный файл резервной копией.

Откат кода: отдельный revert-коммит через PR после анализа совместимости со схемой БД. Повтор старого workflow не откатывает БД и не удаляет лишние файлы.
Не выполнять автоматический migrate:rollback: он может потерять данные или нарушить новую схему.
Backup 3Row-before-deploy-* содержит прежнюю папку ДО первой публикации, а не backup каждой версии приложения.
Для надёжного rollback будущая задача — releases + shared storage/.env + атомарная ссылка и отдельная стратегия DB; это сейчас НЕ реализовано.

## 17. Предупреждения и технический долг

1. checkout@v4 и setup-node@v4 показывают предупреждение о runtime Node 20. node-version:24 относится к сборке, не исправляет runtime action. Выбирать поддерживаемые версии Actions по release notes и проверять отдельным PR.
2. ubuntu-latest предупреждает о смене образа. Можно отдельно закрепить ubuntu-24.04; пока текущий YAML использует ubuntu-latest.
3. npm audit ранее показывал esbuild/Vite advisory. Не считать его исправленным: dependencies фронтенда не обновлялись. Повторить npm audit; не применять force upgrade вслепую.
4. CI не запускает функциональные тесты: добавить безопасный test job на PHP 8.2.
5. Из Git исключить runtime-кэши/сессии, которые исторически уже отслеживались. Делать отдельно, не удаляя production storage.
6. Проверить .env.beget и историю на реальные секреты; при обнаружении ротировать, одной строки .gitignore недостаточно.
7. Общий доступ Beget сейчас шире минимального: согласовать с поддержкой read-only приложения и writable storage/cache, наследование новым файлам.
8. Начальные игровые данные, cron, queue и полная функциональность — отдельные задачи.
9. Health check усилить проверкой конечного URL, маркера ответа и безопасных API smoke checks.
10. Неатомарный deployment и отсутствие cleanup устаревших файлов — отдельная задача, без необдуманного --delete.

## 18. Правила работы KimiCode

- Сначала прочитать этот документ, актуальные workflow/composer/vite/bootstrap/config и git status.
- Отделять подтверждённое состояние от предположений и будущих задач.
- Не заявлять, что игра работает полностью, исходя только из /up и главной страницы.
- Не коммитить секреты, серверный .env, vendor, node_modules, public/build, runtime-файлы.
- Не перегенерировать production APP_KEY.
- Не менять production вручную без согласования и не затирать незакоммиченные изменения владельца.
- Не создавать второй workflow, независимо публикующий тот же main.
- Не перемещать сайт из /3Row/ в корень и не повреждать roman-provinces.
- Не отключать host-key verification, platform checks и security audit для получения зелёного CI.
- Для каждой задачи: отдельная ветка, ограниченный diff, тесты, PR, согласованный merge, Actions, smoke check.
- Если задача требует данных/seed: сначала проанализировать существующие команды и безопасность повторного выполнения.
- Запись в production, миграции с удалением данных и merge запускать только с разрешения владельца.

## 19. Короткий запрос для передачи KimiCode

> Прочитай этот документ как handoff по match3-quest. Production уже доступен по https://lab.iig.ru/3Row/, повторный деплой зелёный. Сохрани URL /3Row/, размещение приложения вне public_html и текущий workflow. Сначала проверь git status и актуальный main; в ~/projects/match3-quest могут оставаться важные незакоммиченные изменения, не уничтожай их. Проверь интерфейс, API, наличие начальных данных и тесты. Составь план следующей задачи, выполняй изменения в отдельной ветке и не запускай production-запись/merge без согласования. Не повторяй настройку SSH/БД, не меняй ACL через setfacl: общий доступ управляется панелью Beget.

## 20. Поддержание документа

Рекомендуемый путь в репозитории: docs/BEGET_DEPLOY_RUNBOOK.md.
Добавить отдельным PR; документация в main тоже запускает текущий push workflow.
При изменении путей, PHP, зависимостей, workflow или настроек общего доступа обновлять документ в том же PR.
Этот файл — переносимый источник контекста; наличие долговременной памяти ассистента не следует считать гарантированным.
