<?php

namespace Madridianfox\LaravelMetrics\Tests;


use Illuminate\Support\Facades\Route;
use Madridianfox\LaravelMetrics\LabelMiddlewares\HttpRequestLabelMiddleware;

class LabelMiddlewareTest extends TestCase
{
    public function testEndpointLabelMiddleware()
    {
        Route::shouldReceive('current')
            ->once()
            ->andReturn(tap(new \stdClass(), fn($route) => $route->uri = "api/login"));

        $labelMiddleware = new HttpRequestLabelMiddleware();

        $this->assertEquals(['endpoint'], $labelMiddleware->labels());
        $this->assertEquals(['GET api/login'], $labelMiddleware->values());
    }
}