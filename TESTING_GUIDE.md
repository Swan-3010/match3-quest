# Фаза 8: Руководство по тестированию Match Quest

## Дата: 2026-09-04

---

## 1. Unit-тестирование (PHPUnit)

### Запуск
```bash
cd /path/to/laravel
php artisan test
# Или с покрытием:
php artisan test --coverage
```

### Что тестируем

| Тест | Что проверяет | Критерий успеха |
|------|--------------|-----------------|
| `AuthTest` | Регистрация, вход, выход, валидация | 200/201 для успеха, 401/422 для ошибок |
| `LevelTest` | Старт уровня, завершение (win/fail), античит | Сессия создаётся, прогресс обновляется, невозможный score отклоняется |
| `ShopTest` | Список товаров, покупка, недостаточно средств | Инвентарь пополняется, баланс уменьшается, 402 при нехватке |
| `DailyRewardTest` | Статус, получение, двойное получение | Streak растёт, 429 при повторе |
| `BoardGeneratorTest` | Генерация поля без матчей, наличие хода | `hasMatches() === false`, `hasAnyMove() === true` |
| `ScoreCalculatorTest` | Подсчёт очков, каскадный бонус | match-3 = 60, match-4 = 150, каскад ×3 = 84 |
| `LifeRegenServiceTest` | Восстановление жизней | +1 за 3ч, capped at 5, multiple lives |

### Добавление нового теста
```php
public function test_feature_description(): void
{
    // Arrange
    $input = ...;
    // Act
    $result = $service->method($input);
    // Assert
    $this->assertEquals($expected, $result);
}
```

---

## 2. Feature/Integration тестирование (API)

### Запуск
```bash
php artisan test --filter=LevelTest
php artisan test --filter=ShopTest
```

### Сценарии

#### Сценарий 1: Полный цикл игры
```
1. POST /auth/register → получить token
2. GET /user/progress → lives = 5, coins = 500
3. GET /levels → список уровней
4. POST /levels/1/start → получить session_id
5. POST /levels/1/complete (win, score=2000) → success=true, next_level=true
6. GET /user/progress → max_level_reached = 2, coins > 500
7. POST /shop/purchase (hammer) → inventory обновлён, coins уменьшены
```

#### Сценарий 2: Поражение и трата жизни
```
1. POST /levels/1/complete (fail, score=500) → success=false
2. GET /user/progress → lives = 4
3. Повторить 5 раз → lives = 0
4. POST /levels/2/start → 403 (нет жизней)
```

#### Сценарий 3: Ежедневный бонус
```
1. GET /daily/status → can_claim_today = true
2. POST /daily/claim → reward получен
3. GET /daily/status → can_claim_today = false
4. POST /daily/claim → 429
```

---

## 3. Ручное тестирование на устройствах

### Подготовка
1. Задеплоить на Beget (или запустить `php artisan serve` + ngrok)
2. Открыть Chrome DevTools → Device Mode
3. Выбрать устройства: Galaxy S8 (360×640), iPhone SE (375×667), iPhone 14 (390×844), Pixel 5 (393×851)

### Чек-лист эргономики

| Проверка | Метод | Критерий |
|----------|-------|----------|
| Тайл ≥ 32px | DevTools → Elements → computed width | ≥ 32px на 360px ширине |
| Палец попадает | Реальное устройство, 10 попыток | ≤ 1 промах |
| Поле не обрезается | Скриншот | Все 9×9 тайлов видны |
| UI не перекрывает | Скриншот | Top bar + boosters + board + bottom nav = комфортно |
| Landscape | Повернуть телефон | Бустеры скрываются, поле видно |

### Чек-лист игровой механики

| Проверка | Шаги | Ожидаемый результат |
|----------|------|---------------------|
| Свайп | Провести пальцем между соседними тайлами | Тайлы меняются местами |
| Match-3 | Собрать 3 в ряд | Тайлы исчезают, очки начислены, новые падают |
| Match-4 | Собрать 4 в ряд | Создаётся ракета |
| Match-5 | Собрать 5 в ряд | Создаётся диско-шар |
| Комбо | Свайпнуть ракету в ракету | Крестообразная очистка |
| Каскад | Сделать ход, вызывающий цепную реакцию | Бонус +20% за каждое звено |
| Нет ходов | Заполнить поле без матчей | Авто-перемешивание |
| Бустер | Тапнуть молоток → тапнуть тайл | Тайл уничтожен без хода |
| Жизни | Проиграть уровень | -1 жизнь, следующий уровень открыт |

### Чек-лист экранов

| Экран | Проверки |
|-------|----------|
| Auth | Регистрация, вход, ошибки валидации, гостевой режим |
| Menu | Статистика актуальна, кнопки работают, бейдж ежедневного бонуса |
| Map | Scroll к текущему уровню, звёзды на пройденных, замки на закрытых |
| Game | Canvas загружается, свайпы отзывчивы (< 100ms), анимации 60fps |
| Shop | 4 вкладки, цены корректны, покупка меняет баланс |
| Profile | Данные совпадают с API, XP bar заполнен |
| Daily | Календарь корректен, кнопка disabled после получения |
| Settings | Тогглы работают, выход очищает сессию |

---

## 4. Performance тестирование

### Метрики

| Метрика | Целевое значение | Как измерить |
|---------|------------------|--------------|
| FCP (First Contentful Paint) | < 1.5s | Lighthouse |
| TTI (Time to Interactive) | < 3s | Lighthouse |
| FPS в игре | 60 | Chrome DevTools → Performance |
| API response time | < 200ms | DevTools → Network |
| Memory usage | < 100MB | DevTools → Memory |

### Нагрузочный тест
```bash
# На сервере Beget
php load_test.php https://lab.iig.ru 10 100

# Ожидаемые результаты:
# - Успешно: 100%
# - Среднее время: < 300ms
# - 95-й перцентиль: < 500ms
```

### Проверка на бюджетном Android
1. Открыть игру на устройстве с 2GB RAM
2. Играть 10 минут
3. Проверить: нет ли lag, не падает ли FPS ниже 30, не перегревается ли устройство

---

## 5. Security тестирование

| Угроза | Проверка | Митигация |
|--------|----------|-----------|
| SQL Injection | `' OR 1=1 --` в поиске | Eloquent/Query Builder (параметризованные запросы) |
| XSS | `<script>alert(1)</script>` в username | Blade auto-escaping `{{ }}` |
| CSRF | Проверка токена на mutating requests | CSRF middleware |
| Накрутка очков | Отправить score=999999999 | AntiCheatService (upper bound) |
| Brute force | 100 запросов login подряд | Rate limit (throttle) |
| JWT theft | Перехват токена | HTTPS only, short TTL, refresh tokens |
| IDOR | Доступ к чужому прогрессу | `where('user_id', auth()->id())` на всех запросах |

### Проверка rate limiting
```bash
for i in {1..20}; do
  curl -X POST https://lab.iig.ru/api/auth/login -d "email=test@test.com&password=wrong"
done
# Ожидаемо: после 5 попыток — 429 Too Many Requests
```

---

## 6. Regression тестирование

### Перед каждым релизом
```bash
# 1. Все тесты проходят
php artisan test

# 2. Сидеры работают
php artisan migrate:fresh --seed

# 3. Генерация уровней работает
php artisan levels:generate 500

# 4. Конфиг кэшируется
php artisan config:cache

# 5. Роуты кэшируются
php artisan route:cache

# 6. Проверка на продакшене
# - Регистрация нового пользователя
# - Прохождение уровней 1–5
# - Покупка в магазине
# - Ежедневный бонус
```

---

## 7. Чек-лист перед релизом MVP

- [ ] Все PHPUnit тесты проходят (`php artisan test`)
- [ ] Все Jest тесты проходят (`npx jest`)
- [ ] Ручное тестирование на 3+ устройствах
- [ ] Lighthouse score ≥ 80
- [ ] Нагрузочный тест: 95-й перцентиль < 500ms
- [ ] Security checklist пройден
- [ ] Админка доступна и работает
- [ ] Backup БД настроен
- [ ] SSL сертификат активен
- [ ] PWA манифест (если готов)

---

## 8. Инструменты

| Инструмент | Назначение |
|-----------|-----------|
| PHPUnit | Backend unit/feature tests |
| Jest | Frontend unit tests |
| Laravel Dusk | E2E browser tests (опционально) |
| Lighthouse | Performance, accessibility, PWA |
| Chrome DevTools | Device emulation, Performance, Network |
| Postman / Insomnia | API ручное тестирование |
| `load_test.php` | Нагрузочный тест |
| `php artisan test` | Запуск всех тестов |
