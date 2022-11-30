# General prometheus metrics for laravel

Пакет добавляет метрики общего назначения для веб-приложения на laravel.
Является дополнением к [MadridianFox/laravel-prometheus](https://github.com/MadridianFox/laravel-prometheus)

## Installation

Добавьте пакет в приложение
```bash
composer require madridianfox/laravel-metrics
```

Скопируйте конфигурацию для дальнейшей настройки
```bash
php artisan vendor:publish --tag=metrics-config
```

Добавьте Http Middleware
```php
# app/Http/Kernel.php

protected $middleware = [
    // ... other middlewares
    \Madridianfox\LaravelMetrics\Middleware\HttpMetricsMiddleware::class,
];
```

Добавьте Guzzle Middleware к вашим http клиентам
```php
$handlerStack = HandlerStack::create();

$handlerStack->push(GuzzleMiddleware::middleware());

$client = new Client(['handler' => $handlerStack]);
$response1 = $client->get('http://httpbin.org/get');
```

## Usage

Метрики:

| Name                          | Type | Labels               | Description |
|-------------------------------| ---- |----------------------| ----------- |
| http_requests_total           | Counter | code, endpoint       | Счётчик входящих http запросов |
| http_request_duration_seconds | Counter| code, type, endpoint | Счётчик времени обработки входящих http запросов |
| http_stats_\<name\>           | Histogram or Summary |                      | Статистика по времени обработки запросов для указанной в конфиге группы эндпоинтов |
| log_messages_count            | Counter | level, endpoint      | Количество сообщений в логе |


## License
Laravel Metrics is open-sourced software licensed under the [MIT license](LICENSE.md).