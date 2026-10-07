# Настройка админ-панели

1. Добавить middleware в bootstrap/app.php:
```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'admin' => \App\Http\Middleware\AdminAuth::class,
    ]);
})
```

2. Добавить route file в bootstrap/app.php:
```php
->withRouting(
    web: __DIR__.'/../routes/web.php',
    api: __DIR__.'/../routes/api.php',
    commands: __DIR__.'/../routes/console.php',
    // Добавить:
    // admin: __DIR__.'/../routes/admin.php',
)
```

3. Создать админ-пользователя:
```sql
INSERT INTO users (email, password_hash, email_verified_at, created_at) 
VALUES ('admin@matchquest.ru', '$2y$12$...', NOW(), NOW());
```
