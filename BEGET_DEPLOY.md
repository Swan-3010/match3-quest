# Деплой Match Quest на Beget (lab.iig.ru)

## 1. Подготовка сервера (Beget)

### 1.1 Создание сайта
1. Войти в панель управления Beget
2. Раздел "Сайты" → "Добавить сайт"
3. Указать домен: `lab.iig.ru`
4. PHP версия: **8.2** (обязательно!)
5. Режим: `mod_php` или `php-fpm`

### 1.2 Создание БД
1. Раздел "MySQL" → "Создать базу"
2. Имя БД: `lab_iig_ru_match3`
3. Создать пользователя, запомнить пароль
4. Обновить `.env.beget` (DB_DATABASE, DB_USERNAME, DB_PASSWORD)

### 1.3 SSL сертификат
1. Раздел "SSL" → "Управление SSL"
2. Выбрать `lab.iig.ru`
3. Заказать Let's Encrypt (бесплатно)
4. Дождаться активации (~5 минут)

### 1.4 Настройка SSH (для GitHub Actions)
1. Раздел "SSH" → включить SSH-доступ
2. Запомнить: хост, порт (обычно 22), имя пользователя
3. Сгенерировать deploy key на локальной машине:
```bash
ssh-keygen -t ed25519 -C "github-actions@matchquest" -f ~/.ssh/matchquest_deploy
```
4. Добавить публичный ключ (`matchquest_deploy.pub`) в Beget:
   - Раздел "SSH" → "Добавить ключ"
5. Приватный ключ (`matchquest_deploy`) добавить в GitHub Secrets:
   - Settings → Secrets and variables → Actions → New repository secret
   - Name: `BEGET_SSH_KEY`
   - Value: содержимое `matchquest_deploy`

### 1.5 GitHub Secrets
Добавить в репозиторий GitHub (Settings → Secrets → Actions):

| Secret | Значение | Пример |
|--------|----------|--------|
| `BEGET_SSH_KEY` | Приватный SSH ключ | `-----BEGIN OPENSSH PRIVATE KEY-----...` |
| `BEGET_HOST` | SSH хост | `your_server.beget.com` |
| `BEGET_USER` | SSH пользователь | `your_login` |
| `BEGET_PORT` | SSH порт (опционально) | `22` |
| `BEGET_PATH` | Путь к сайту | `/home/your_login/lab.iig.ru` |

---

## 2. Настройка Laravel на Beget

### 2.1 Структура директорий
```
/home/your_login/
└── lab.iig.ru/          # Корень сайта (куда деплоим)
    ├── app/
    ├── bootstrap/
    ├── config/
    ├── database/
    ├── public/           # DocumentRoot
    │   ├── index.php
    │   ├── .htaccess
    │   └── assets/
    ├── resources/
    ├── routes/
    ├── storage/
    ├── vendor/
    ├── artisan
    └── .env
```

### 2.2 Права на директории
```bash
# На сервере (через SSH)
cd /home/your_login/lab.iig.ru
chmod -R 755 .
chmod -R 775 storage
chmod -R 775 bootstrap/cache
```

### 2.3 Симлинк storage
```bash
php artisan storage:link
```

---

## 3. Настройка Cron (жизни)

### 3.1 Через панель Beget
1. Раздел "Cron" → "Добавить задачу"
2. Команда:
```
cd /home/your_login/lab.iig.ru && php artisan schedule:run >> /dev/null 2>&1
```
3. Расписание: каждую минуту (`* * * * *`)

### 3.2 Проверка
```bash
php artisan schedule:list
```

---

## 4. Первый деплой (ручной)

### 4.1 Локально
```bash
# Клонируем репозиторий
git clone git@github.com:Swan-3010/match3-quest.git
cd match3-quest

# Устанавливаем зависимости
composer install --no-dev --optimize-autoloader
npm ci && npm run build

# Копируем .env
cp .env.beget .env
# Редактируем DB_PASSWORD в .env

# Генерируем ключ
php artisan key:generate

# Кэшируем конфиг
php artisan config:cache
php artisan route:cache
```

### 4.2 Загружаем на сервер
```bash
# Через rsync (или FileZilla)
rsync -avz --delete   --exclude=.git   --exclude=tests   --exclude=node_modules   --exclude=.github   ./ your_login@your_server.beget.com:/home/your_login/lab.iig.ru/
```

### 4.3 На сервере
```bash
ssh your_login@your_server.beget.com
cd /home/your_login/lab.iig.ru

# Миграции
php artisan migrate --force

# Сидеры
php artisan db:seed

# Генерация уровней
php artisan levels:generate 500

# Кэш
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## 5. Автоматический деплой (GitHub Actions)

### 5.1 Триггер
При push в `main`:
1. Запускаются PHPUnit тесты
2. Собираются ассеты (vite build)
3. Деплой через rsync на Beget
4. Post-deploy: миграции + кэш

### 5.2 Мониторинг
- GitHub Actions → вкладка "Actions" в репозитории
- При ошибке — email уведомление
- Логи деплоя в real-time

---

## 6. Проверка после деплоя

### 6.1 Быстрая проверка
```bash
curl -I https://lab.iig.ru
# Ожидаемо: HTTP/2 200

curl https://lab.iig.ru/api/levels
# Ожидаемо: JSON с уровнями или 401 (если без токена)
```

### 6.2 Проверка функционала
1. Открыть `https://lab.iig.ru` в браузере
2. Зарегистрироваться
3. Пройти уровень 1
4. Проверить магазин
5. Проверить ежедневный бонус
6. Открыть `/admin` → войти админом

### 6.3 Проверка PWA
1. Chrome DevTools → Lighthouse → PWA
2. Должно показать: "Installable"
3. На Android: "Добавить на главный экран"

---

## 7. Решение проблем

### 7.1 Ошибка 500
```bash
# Проверить логи
tail -f storage/logs/laravel.log

# Проверить права
ls -la storage/
chmod -R 775 storage bootstrap/cache
```

### 7.2 Ошибка 404 на API
```bash
# Проверить .htaccess
cat public/.htaccess

# Проверить mod_rewrite
php -m | grep rewrite
```

### 7.3 БД недоступна
```bash
# Проверить .env
cat .env | grep DB_

# Проверить подключение
php artisan tinker
>>> DB::connection()->getPdo();
```

### 7.4 GitHub Actions падает
1. Проверить Secrets (все 5 переменных)
2. Проверить SSH ключ (должен быть ed25519, без passphrase)
3. Проверить права на сервере

---

## 8. Backup

### 8.1 Автоматический бэкап БД (cron)
```bash
# Добавить в cron (раз в день)
0 3 * * * mysqldump -u lab_iig_ru_user -p'PASSWORD' lab_iig_ru_match3 > /home/your_login/backups/match3_$(date +\%Y\%m\%d).sql
```

### 8.2 Ручной бэкап
```bash
php artisan db:dump
# Или:
mysqldump -h localhost -u USER -p DB_NAME > backup.sql
```
