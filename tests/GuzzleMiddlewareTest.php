<?php

namespace Ensi\LaravelMetrics\Tests;

use Exception;
use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Promise\RejectedPromise;
use GuzzleHttp\Psr7\Request;
use Ensi\LaravelMetrics\Guzzle\GuzzleMiddleware;
use Ensi\LaravelMetrics\LatencyProfiler;
use Mockery\MockInterface;

class GuzzleMiddlewareTest extends TestCase
{
    public function testSimpleRequest(): void
    {
        /** @var LatencyProfiler|MockInterface $latencyProfiler */
        $latencyProfiler = $this->mock(LatencyProfiler::class);
        $latencyProfiler->expects('addAsyncTimeQuant');

        $middleware = GuzzleMiddleware::middleware();
        $expectedRequest = new Request('GET', 'https://example.org');
        $expectedOptions = [];
        $expectedResponse = 'simple response';

        $next = $middleware(function ($request, $options) use ($expectedRequest, $expectedOptions, $expectedResponse) {
            $this->assertSame($expectedRequest, $request);
            $this->assertSame($expectedOptions, $options);

            return $expectedResponse;
        });

        $response = $next($expectedRequest, $expectedOptions);

        $this->assertSame($expectedResponse, $response);
    }

    public function testAsyncRequest(): void
    {
        /** @var LatencyProfiler|MockInterface $latencyProfiler */
        $latencyProfiler = $this->mock(LatencyProfiler::class);
        $latencyProfiler->expects('addAsyncTimeQuant');

        $middleware = GuzzleMiddleware::middleware();
        $expectedRequest = new Request('GET', 'https://example.org');
        $expectedOptions = [];
        $expectedResponseValue = "ok";
        $expectedResponse = new FulfilledPromise($expectedResponseValue);

        $next = $middleware(function ($request, $options) use ($expectedRequest, $expectedOptions, $expectedResponse) {
            $this->assertSame($expectedRequest, $request);
            $this->assertSame($expectedOptions, $options);

            return $expectedResponse;
        });

        /** @var PromiseInterface $response */
        $response = $next($expectedRequest, $expectedOptions);

        $this->assertEquals($expectedResponseValue, $response->wait(true));
    }

    public function testFailedAsyncRequest(): void
    {
        /** @var LatencyProfiler|MockInterface $latencyProfiler */
        $latencyProfiler = $this->mock(LatencyProfiler::class);
        $latencyProfiler->expects('addAsyncTimeQuant');

        $middleware = GuzzleMiddleware::middleware();
        $expectedRequest = new Request('GET', 'https://example.org');
        $expectedOptions = [];
        $expectedResponseValue = new Exception("error");

        $next = $middleware(function ($request, $options) use ($expectedRequest, $expectedOptions, $expectedResponseValue) {
            $this->assertSame($expectedRequest, $request);
            $this->assertSame($expectedOptions, $options);

            return new RejectedPromise($expectedResponseValue);
        });

        try {
            $next($expectedRequest, $expectedOptions)->wait(true);
        } catch (Exception $e) {
            $this->assertSame($expectedResponseValue, $e);
        }
    }
}