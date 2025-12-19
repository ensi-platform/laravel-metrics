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
            // If your app runs in multiple containers and each of them is responsible for its own metrics,
            // then you don't need to use the "summary"
            'type' => 'summary',
            'route_names' => ['*'], // or use prefix, like ['catalog.*', 'profile.favorites'],
            'time_window' => 30,
            'quantiles' => [0.5, 0.75, ,0.95],
        ],
        '<stats-group-name>' => [
            'type' => 'histogram',
            'route_names' => ['*'], // or use prefix, like ['catalog.*', 'profile.favorites'],
            'buckets' => [0.005, 0.01, 0.025, 0.05, 0.1, 0.25, 0.5, 1, 2.5, 5, 10],
        ],
    ],
    'http_client_per_path' => [
        'domains' => ['*'], // or list like ['domain1.com', 'domain2.com']
    ],
    'http_client_stats' => [
        'domains' => ['*'], // or list like ['domain1.com', 'domain2.com']
        'buckets' => [0.005, 0.01, 0.025, 0.05, 0.1, 0.25, 0.5, 1, 2.5, 5, 10],
    ]
];
```

**ignore_routes** - a list of names of routes for which you do not need to track the processing time of http requests.  
**ignore_commands** - a list of team names for which you do not need to track metrics.  
**http_requests_stats_groups** - a list of histograms and percentiles. Each stats group has a list of the names of the routes that it tracks.  
Thus, you can count statistics not for the entire application, but for individual groups of endpoints.  
**http_client_per_path** - configuration for collecting per-path metrics for outgoing HTTP client requests. Specify domains to track request counts and durations per path.  
**http_client_stats** - configuration for collecting histogram statistics on outgoing HTTP client request processing time for specified domains.

## Metrics

The names of the metrics are presented without the namespace.

| Name                            | Type                 | Labels                 | Description                                                                          |
|---------------------------------|----------------------|------------------------|--------------------------------------------------------------------------------------|
| http_requests_total             | Counter              | code, endpoint         | Counter of incoming http requests                                                    |
| http_request_duration_seconds   | Counter              | code, type, endpoint   | Time counter for processing incoming http requests                                   |
| http_stats_\<name\>             | Histogram or Summary |                        | Statistics on request processing time for the endpoint group specified in the config |
| http_client_requests_total      | Counter              | host                   | Counter of outgoing HTTP client requests                                             |
| http_client_seconds_total       | Counter              | host                   | Time counter for outgoing HTTP client requests                                       |
| http_client_path_requests_total | Counter              | host, path             | Counter of outgoing HTTP client requests per path                                    |
| http_client_path_seconds_total  | Counter              | host, path             | Time counter for outgoing HTTP client requests per path                              |
| http_client_stats               | Histogram            | host, path             | Statistics on outgoing HTTP client request processing time                           |
| log_messages_count              | Counter              | level, endpoint        | Number of messages in the log                                                        |
| queue_job_dispatched_total      | Counter              | connection, queue, job | The number of jobs sent to the queue                                                 |
| queue_job_runs_total            | Counter              | connection, queue, job | The number of processed jobs in the queue                                            |
| queue_job_run_seconds_total     | Counter              | connection, queue, job | Time counter for completing tasks in the queue                                       |
| command_runs_total              | Counter              | command, status        | Number of completed commands                                                         |
| command_run_seconds_total       | Counter              | command, status        | Command execution time counter                                                       |
| workers_total                   | Gauge                | worker                 | Number of swoole workers                                                             |
| workers_idle                    | Gauge                | worker                 | Number of free swoole workers                                                        |

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

### Testing

1. composer install
2. composer test

## Security Vulnerabilities

Please review [our security policy](.github/SECURITY.md) on how to report security vulnerabilities.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
