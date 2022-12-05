<?php

namespace Madridianfox\LaravelMetrics\Guzzle;

use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Promise\RejectedPromise;
use Madridianfox\LaravelMetrics\LatencyProfiler;
use Psr\Http\Message\RequestInterface;
use function resolve;

class GuzzleMiddleware
{
    public static function middleware()
    {
        return function(callable $handler) {
            return static function(RequestInterface $request, array $options) use ($handler) {
                $start = microtime(true);
                $response = $handler($request, $options);
                if ($response instanceof PromiseInterface) {
                    return $response->then(function ($result) use ($start, $request) {
                        self::handleResponse($start, $request);
                        return $result;
                    })->otherwise(function ($reason) use ($start, $request) {
                        self::handleResponse($start, $request);
                        return new RejectedPromise($reason);
                    });
                } else {
                    self::handleResponse($start, $request);
                }

                return $response;
            };
        };
    }

    public static function handleResponse($start, $request)
    {
        $end = microtime(true);

        /** @var LatencyProfiler $profiler */
        $profiler = resolve(LatencyProfiler::class);
        $profiler->addAsyncTimeQuant('http_client', $start, $end);
    }
}