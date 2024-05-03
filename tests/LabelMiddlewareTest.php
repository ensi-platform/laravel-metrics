<?php

namespace Ensi\LaravelMetrics\Tests;

use Ensi\LaravelMetrics\Labels\HttpRequestLabels;
use Illuminate\Support\Facades\Route;

class LabelMiddlewareTest extends TestCase
{
    public function testEndpointLabelMiddleware()
    {
        Route::shouldReceive('current')
            ->once()
            ->andReturn(tap(new \stdClass(), fn ($route) => $route->uri = "api/login"));

        $labelMiddleware = new HttpRequestLabels();

        $this->assertEquals(['endpoint'], $labelMiddleware->labels());
        $this->assertEquals(['GET api/login'], $labelMiddleware->values());
    }
}
