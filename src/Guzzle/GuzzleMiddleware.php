<?php

namespace Ensi\LaravelMetrics\Guzzle;

use Ensi\LaravelMetrics\LatencyProfiler;
use Ensi\LaravelPrometheus\Prometheus;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Promise\RejectedPromise;
use Psr\Http\Message\RequestInterface;

use function resolve;

class GuzzleMiddleware
{
    public static function middleware(string $type = 'http_client')
    {
        return function (callable $handler) use ($type) {
            return static function (RequestInterface $request, array $options) use ($type, $handler) {
                $start = microtime(true);
                $response = $handler($request, $options);
                if ($response instanceof PromiseInterface) {
                    return $response->then(function ($result) use ($type, $start, $request) {
                        self::handleResponse($type, $start, $request->getHeaderLine('host'), $request->getUri()->getPath());

                        return $result;
                    })->otherwise(function ($reason) use ($type, $start, $request) {
                        self::handleResponse($type, $start, $request->getHeaderLine('host'), $request->getUri()->getPath());

                        return new RejectedPromise($reason);
                    });
                } else {
                    self::handleResponse($type, $start, $request->getHeaderLine('host'), $request->getUri()->getPath());
                }

                return $response;
            };
        };
    }

    public static function handleResponse(string $type, $start, string $host, string $uriPath): void
    {
        $end = microtime(true);

        /** @var LatencyProfiler $profiler */
        $profiler = resolve(LatencyProfiler::class);
        $profiler->addAsyncTimeQuant($type, $start, $end);

        $labels = ['host'];
        Prometheus::update('http_client_seconds_total', $end - $start, $labels);
        Prometheus::update('http_client_requests_total', 1, $labels);

        $httpClientStatsSettings = config('metrics.http_client_stats_settings', []);
        $labelNames = $httpClientStatsSettings[$host]['labels'] ?? null;
        if ($labelNames === null) {
            return;
        }

        foreach ($labelNames as $labelName) {
            $labels[] = match ($labelName) {
                'host' => $host,
                'path' => preg_replace('#/(\d+)(?=/|$)#', '/{id}', $uriPath),
                default => null,
            };
        }
        $labels = array_filter($labels);
        Prometheus::update('http_client_path_seconds_total', $end - $start, $labels);
        Prometheus::update('http_client_path_requests_total', 1, $labels);        
    }
}
