# Фаза 6: Админ-панель — Отчёт

## Дата: 2026-09-04

### Архитектура

```
/admin (prefix)
├── GET  /                  → Dashboard (статистика, DAU, топ уровней)
├── GET  /levels            → Список уровней + импорт CSV + генерация
├── GET  /levels/<built-in function id>/edit  → Редактирование уровня
├── PUT  /levels/<built-in function id>       → Сохранение изменений
├── POST /levels/import     → Импорт из CSV
├── POST /levels/generate   → Генерация 1–500 уровней
├── GET  /features          → Список фишек
├── GET  /features/<built-in function id>/edit→ Редактирование фишки
├── PUT  /features/<built-in function id>     → Сохранение
├── GET  /imbas             → Список имб
├── GET  /imbas/<built-in function id>/edit   → Редактирование имбы
├── PUT  /imbas/<built-in function id>        → Сохранение
├── GET  /shop              → Список товаров
├── GET  /shop/<built-in function id>/edit    → Редактирование товара
├── PUT  /shop/<built-in function id>         → Сохранение
├── GET  /users             → Список игроков
├── GET  /users/<built-in function id>        → Детали игрока (прогресс, инвентарь, сессии)
└── GET  /analytics         → Сложность уровней, retention, события
```

### Защита

- Middleware `admin` — проверяет `email_verified_at` и наличие "admin" в email
- В продакшене рекомендуется Spatie Laravel Permission
- Все роуты под `auth:sanctum + admin`

### Файлы админки

| Файл | Описание |
|------|----------|
| `app/Http/Middleware/AdminAuth.php` | Middleware проверки прав |
| `routes/admin.php` | Роуты админки |
| `app/Http/Controllers/Admin/DashboardController.php` | Дашборд со статистикой |
| `app/Http/Controllers/Admin/LevelAdminController.php` | CRUD уровней + CSV импорт |
| `app/Http/Controllers/Admin/FeatureAdminController.php` | CRUD фишек |
| `app/Http/Controllers/Admin/ImbaAdminController.php` | CRUD имб |
| `app/Http/Controllers/Admin/ShopAdminController.php` | CRUD магазина |
| `app/Http/Controllers/Admin/UserAdminController.php` | Просмотр игроков |
| `app/Http/Controllers/Admin/AnalyticsAdminController.php` | Аналитика |
| `resources/views/admin/layout.blade.php` | Базовый layout (sidebar + main) |
| `resources/views/admin/dashboard.blade.php` | Дашборд |
| `resources/views/admin/levels.blade.php` | Список уровней |
| `resources/views/admin/levels_edit.blade.php` | Редактор уровня |
| `resources/views/admin/features.blade.php` | Список фишек |
| `resources/views/admin/features_edit.blade.php` | Редактор фишки |
| `resources/views/admin/imbas.blade.php` | Список имб |
| `resources/views/admin/imbas_edit.blade.php` | Редактор имбы |
| `resources/views/admin/shop.blade.php` | Список товаров |
| `resources/views/admin/shop_edit.blade.php` | Редактор товара |
| `resources/views/admin/users.blade.php` | Список игроков |
| `resources/views/admin/users_show.blade.php` | Детали игрока |
| `resources/views/admin/analytics.blade.php` | Аналитика |
| `ADMIN_SETUP.md` | Инструкция по настройке |

### Возможности админки

1. **Дашборд** — DAU, винрейт, попытки, монеты, активные игроки
2. **Уровни** — редактирование moves, target, stars, colors, is_active; импорт CSV; массовая генерация
3. **Фишки** — редактирование unlock_level, name, condition, is_active
4. **Имбы** — редактирование цен, is_active
5. **Магазин** — редактирование цен, категорий, сортировки, is_active
6. **Игроки** — просмотр прогресса, ресурсов, инвентаря, истории сессий
7. **Аналитика** — сложность уровней (win rate, avg score), retention, события

### Всего файлов проекта: 90
### Общий размер: 160 KB
