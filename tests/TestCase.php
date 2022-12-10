<?php

namespace Ensi\LaravelMetrics\Tests;

class TestCase extends \Orchestra\Testbench\TestCase
{
    protected function getPackageProviders($app)
    {
        return [
            \Ensi\LaravelPrometheus\PrometheusServiceProvider::class,
            \Ensi\LaravelMetrics\MetricsServiceProvider::class,
        ];
    }
}