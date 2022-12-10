<?php

namespace Ensi\LaravelMetrics\Tests;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Ensi\LaravelMetrics\LatencyProfiler;
use Ensi\LaravelMetrics\HttpMiddleware\HttpMetricsMiddleware;
use Mockery\MockInterface;

class HttpMetricsMiddlewareTest extends TestCase
{
    public function testMiddleware()
    {
        /** @var LatencyProfiler|MockInterface $latencyProfiler */
        $latencyProfiler = $this->mock(LatencyProfiler::class);
        $latencyProfiler->expects('writeMetrics');

        $middleware = new HttpMetricsMiddleware($latencyProfiler);
        $expectedRequest = new Request();
        $expectedResponse = new Response();

        $response = $middleware->handle($expectedRequest, function (Request $request) use ($expectedRequest, $expectedResponse) {
            $this->assertSame($expectedRequest, $request);

            return $expectedResponse;
        });

        $this->assertSame($expectedResponse, $response);
    }
}