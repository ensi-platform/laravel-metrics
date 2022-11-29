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

Пакет добавляет в дефолтный Metrics Bag свой Label Middleware - HttpRequestLabelMiddleware,
который добавляет во все метрики лейбл endpoint содержащий шаблон текущего роута.

Метрики:
| Name | Type | Labels | Description |
| ---- | ---- | ------ | ----------- |
| http_requests_total | Counter | code, endpoint ||
| http_request_duration_seconds | Counter| code, type, endpoint ||
| http_stats_<name> | histogram or summery | 


## License
Laravel Prometheus is open-sourced software licensed under the [MIT license](LICENSE.md).