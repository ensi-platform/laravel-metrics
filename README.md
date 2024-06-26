# General prometheus metrics for laravel

[![Latest Version on Packagist](https://img.shields.io/packagist/v/ensi/laravel-metrics.svg?style=flat-square)](https://packagist.org/packages/ensi/laravel-metrics)
[![Tests](https://github.com/ensi-platform/laravel-metrics/actions/workflows/run-tests.yml/badge.svg?branch=master)](https://github.com/ensi-platform/laravel-metrics/actions/workflows/run-tests.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/ensi/laravel-metrics.svg?style=flat-square)](https://packagist.org/packages/ensi/laravel-metrics)

The package adds general-purpose metrics for a laravel web application.
It is an addition to[ensi/laravel-prometheus](https://github.com/ensi-platform/laravel-prometheus)

## Installation

You can install the package via composer:

```bash
composer require ensi/laravel-metrics
```

Publish the config with:

```bash
php artisan vendor:publish --provider="Ensi\LaravelMetrics\MetricsServiceProvider"
```

## Basic Usage

Add Http Middleware

```php
# app/Http/Kernel.php

protected $middleware = [
    // ... other middlewares
    \Ensi\LaravelMetrics\HttpMiddleware\HttpMetricsMiddleware::class,
];
```

Add Guzzle Middleware to your http clients

```php
$handlerStack = HandlerStack::create();

$handlerStack->push(GuzzleMiddleware::middleware());

$client = new Client(['handler' => $handlerStack]);
$response1 = $client->get('http://httpbin.org/get');
```

# Configuration

The structure of the configuration file

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

**ignore_routes** - a list of names of routes for which you do not need to track the processing time of http requests.  
**ignore_commands** - a list of team names for which you do not need to track metrics.  
**http_requests_stats_groups** - a list of histograms and percentiles. Each stats group has a list of the names of the routes that it tracks.  
Thus, you can count statistics not for the entire application, but for individual groups of endpoints.

## Metrics

The names of the metrics are presented without the namespace.

| Name                          | Type                 | Labels                 | Description                                                                          |
|-------------------------------|----------------------|------------------------|--------------------------------------------------------------------------------------|
| http_requests_total           | Counter              | code, endpoint         | Counter of incoming http requests                                                    |
| http_request_duration_seconds | Counter              | code, type, endpoint   | Time counter for processing incoming http requests                                   |
| http_stats_\<name\>           | Histogram or Summary |                        | Statistics on request processing time for the endpoint group specified in the config |
| log_messages_count            | Counter              | level, endpoint        | Number of messages in the log                                                        |
| queue_job_dispatched_total    | Counter              | connection, queue, job | The number of jobs sent to the queue                                                 |
| queue_job_runs_total          | Counter              | connection, queue, job | The number of processed jobs in the queue                                            |
| queue_job_run_seconds_total   | Counter              | connection, queue, job | Time counter for completing tasks in the queue                                       |
| command_runs_total            | Counter              | command, status        | Number of completed commands                                                         |
| command_run_seconds_total     | Counter              | command, status        | Command execution time counter                                                       |
| workers_total                 | Gauge                | worker                 | Number of swoole workers                                                             |
| workers_idle                  | Gauge                | worker                 | Number of free swoole workers                                                        |

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

### Testing

1. composer install
2. composer test

## Security Vulnerabilities

Please review [our security policy](.github/SECURITY.md) on how to report security vulnerabilities.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
