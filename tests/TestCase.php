<?php

namespace Ensi\LaravelMetrics\Tests;

use Ensi\LaravelMetrics\MetricsServiceProvider;
use Ensi\LaravelPrometheus\PrometheusServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            PrometheusServiceProvider::class,
            MetricsServiceProvider::class,
        ];
    }
}
