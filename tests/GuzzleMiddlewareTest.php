<?php

namespace Ensi\LaravelMetrics\Tests;

use Ensi\LaravelMetrics\Guzzle\GuzzleMiddleware;
use Ensi\LaravelMetrics\LatencyProfiler;
use Exception;
use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Promise\RejectedPromise;
use GuzzleHttp\Psr7\Request;
use Mockery\MockInterface;

use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertSame;

uses(TestCase::class);

test('test simple request', function () {
    /** @var TestCase $this */

    /** @var LatencyProfiler|MockInterface $latencyProfiler */
    $latencyProfiler = $this->mock(LatencyProfiler::class);
    $latencyProfiler->expects('addAsyncTimeQuant');

    $middleware = GuzzleMiddleware::middleware();
    $expectedRequest = new Request('GET', 'https://example.org');
    $expectedOptions = [];
    $expectedResponse = 'simple response';

    $next = $middleware(function ($request, $options) use ($expectedRequest, $expectedOptions, $expectedResponse) {
        assertSame($expectedRequest, $request);
        assertSame($expectedOptions, $options);

        return $expectedResponse;
    });

    $response = $next($expectedRequest, $expectedOptions);

    assertSame($expectedResponse, $response);
});

test('test async request', function () {
    /** @var TestCase $this */

    /** @var LatencyProfiler|MockInterface $latencyProfiler */
    $latencyProfiler = $this->mock(LatencyProfiler::class);
    $latencyProfiler->expects('addAsyncTimeQuant');

    $middleware = GuzzleMiddleware::middleware();
    $expectedRequest = new Request('GET', 'https://example.org');
    $expectedOptions = [];
    $expectedResponseValue = "ok";
    $expectedResponse = new FulfilledPromise($expectedResponseValue);

    $next = $middleware(function ($request, $options) use ($expectedRequest, $expectedOptions, $expectedResponse) {
        assertSame($expectedRequest, $request);
        assertSame($expectedOptions, $options);

        return $expectedResponse;
    });

    /** @var PromiseInterface $response */
    $response = $next($expectedRequest, $expectedOptions);

    assertEquals($expectedResponseValue, $response->wait(true));
});

test('test failed async request', function () {
    /** @var TestCase $this */

    /** @var LatencyProfiler|MockInterface $latencyProfiler */
    $latencyProfiler = $this->mock(LatencyProfiler::class);
    $latencyProfiler->expects('addAsyncTimeQuant');

    $middleware = GuzzleMiddleware::middleware();
    $expectedRequest = new Request('GET', 'https://example.org');
    $expectedOptions = [];
    $expectedResponseValue = new Exception("error");

    $next = $middleware(function ($request, $options) use ($expectedRequest, $expectedOptions, $expectedResponseValue) {
        assertSame($expectedRequest, $request);
        assertSame($expectedOptions, $options);

        return new RejectedPromise($expectedResponseValue);
    });

    try {
        $next($expectedRequest, $expectedOptions)->wait(true);
    } catch (Exception $e) {
        assertSame($expectedResponseValue, $e);
    }
});
