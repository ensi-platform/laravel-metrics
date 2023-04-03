<?php

namespace Ensi\LaravelMetrics\Guzzle;

use Ensi\LaravelPrometheus\Prometheus;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Promise\RejectedPromise;
use Ensi\LaravelMetrics\LatencyProfiler;
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

    /**
     * @param $start
     * @param RequestInterface $request
     */
    public static function handleResponse($start, $request): void
    {
        $end = microtime(true);

        /** @var LatencyProfiler $profiler */
        $profiler = resolve(LatencyProfiler::class);
        $profiler->addAsyncTimeQuant('http_client', $start, $end);

        Prometheus::update('http_client_seconds_total', $end - $start, [
            'host' => $request->getHeaderLine('host')
        ]);
    }
}