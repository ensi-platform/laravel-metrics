<?php

namespace Ensi\LaravelMetrics\Tests;

use Ensi\LaravelMetrics\Labels\HttpRequestLabels;
use Illuminate\Support\Facades\Route;

use function PHPUnit\Framework\assertEquals;

uses(TestCase::class);

test('test endpoint label middleware', function () {
    /** @var TestCase $this */

    Route::shouldReceive('current')
        ->once()
        ->andReturn(tap(new \stdClass(), fn ($route) => $route->uri = "api/login"));

    $labelMiddleware = new HttpRequestLabels();

    assertEquals(['endpoint'], $labelMiddleware->labels());
    assertEquals(['GET api/login'], $labelMiddleware->values());
});
