<?php

namespace Madridianfox\LaravelMetrics\Tests;

class TestCase extends \Orchestra\Testbench\TestCase
{
    protected function getPackageProviders($app)
    {
        return [
            \Madridianfox\LaravelPrometheus\PrometheusServiceProvider::class,
            \Madridianfox\LaravelMetrics\MetricsServiceProvider::class,
        ];
    }
}