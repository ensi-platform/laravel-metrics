<?php

namespace Ensi\LaravelMetrics\Tests;

use Ensi\LaravelMetrics\HttpMiddleware\HttpMetricsMiddleware;
use Ensi\LaravelMetrics\LatencyProfiler;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Mockery\MockInterface;

use function PHPUnit\Framework\assertSame;

uses(TestCase::class);

test('test middleware', function () {
    /** @var TestCase $this */

    /** @var LatencyProfiler|MockInterface $latencyProfiler */
    $latencyProfiler = $this->mock(LatencyProfiler::class);
    $latencyProfiler->expects('writeMetrics');

    $middleware = new HttpMetricsMiddleware($latencyProfiler);
    $expectedRequest = new Request();
    $expectedResponse = new Response();

    $response = $middleware->handle($expectedRequest, function (Request $request) use ($expectedRequest, $expectedResponse) {
        assertSame($expectedRequest, $request);

        return $expectedResponse;
    });

    $middleware->terminate($expectedRequest, $expectedResponse);

    assertSame($expectedResponse, $response);
});
