# General prometheus metrics for laravel

Пакет добавляет метрики общего назначения для веб-приложения на laravel.
Является дополнением к [ensi/laravel-prometheus](https://github.com/ensi-platform/laravel-prometheus)

## Installation

Добавьте пакет в приложение
```bash
composer require ensi/laravel-metrics
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
    \Ensi\LaravelMetrics\HttpMiddleware\HttpMetricsMiddleware::class,
];
```

Добавьте Guzzle Middleware к вашим http клиентам
```php
$handlerStack = HandlerStack::create();

$handlerStack->push(GuzzleMiddleware::middleware());

$client = new Client(['handler' => $handlerStack]);
$response1 = $client->get('http://httpbin.org/get');
```

# Configuration

Структура файла конфигурации
```php
return [
    'ignore_commands' => [
        'kafka:consume',
    ],
    'ignore_routes' => [
        'prometheus.*'
    ],
    'http_requests_stats_groups' => [
        '<stats-group-name>' => [
            'type' => 'summary',
            'route_names' => ['catalog.*', 'profile.favorites'],
            'time_window' => 30,
            'quantiles' => [0.95, 0.99],
        ],
        '<stats-group-name>' => [
            'type' => 'histogram',
            'route_names' => ['*'],
            'buckets' => [0.01, 0.05, 0.1, 0.5, 1, 2, 4],
        ],
    ]
];
```

**ignore_routes** - список имён роутов, для которых не нужно отслеживать время обработки http запросов.
**ignore_commands** - список имён команд, для которых не нужно отслеживать метрики.  
**http_requests_stats_groups** - список гистограмм и перцентилей. Каждая stats группа имеет список имён роутов, которые она отслеживает.
Тем самым вы можете считать статистику не по всему приложению, а по отдельным группам эндпоинтов.

## Metrics

Имена метрик представлены без неймспейса.

| Name                          | Type | Labels               | Description                                                                     |
|-------------------------------| ---- |----------------------|---------------------------------------------------------------------------------|
| http_requests_total           | Counter | code, endpoint       | Счётчик входящих http запросов                                                  |
| http_request_duration_seconds | Counter| code, type, endpoint | Счётчик времени обработки входящих http запросов                                |
| http_stats_\<name\>           | Histogram or Summary |                      | Статистика по времени обработки запросов для указанной в конфиге группы эндпоинтов |
| log_messages_count            | Counter | level, endpoint      | Количество сообщений в логе                                                     |
| queue_job_dispatched_total | Counter | connection, queue, job                  | Количество отправленных заданий в очередь                                       |
| queue_job_runs_total | Counter | connection, queue, job                  | Количество обработанных заданий в очереди                                       |
| queue_job_run_seconds_total | Counter | connection, queue, job                  | Счётчик времени выполнения заданий в очереди                                    |
| command_runs_total | Counter | command, status                  | Количество завершенных команд                                                   |
| command_run_seconds_total | Counter | command, status            | Счётчик времени выполнения команд                                               |
| workers_total | Gauge | worker | Кол-во воркеров swoole |
| workers_idle | Gauge | worker | Кол-во свободных воркеров swoole |


## License
Laravel Metrics is open-sourced software licensed under the [MIT license](LICENSE.md).