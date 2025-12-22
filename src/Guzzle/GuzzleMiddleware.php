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
    /**
     * Create Guzzle middleware for metrics collection
     *
     * @param string $type Metric type identifier
     * @param bool $collectPathMetrics Enable per-path metrics collection (http_client_path_*)
     * @param bool $collectStats Enable detailed statistics collection (http_client_stats)
     * @return callable
     */
    public static function middleware(
        string $type = 'http_client',
        bool $collectPathMetrics = false,
        bool $collectStats = false
    ) {
        return function (callable $handler) use ($type, $collectPathMetrics, $collectStats) {
            return static function (RequestInterface $request, array $options) use ($type, $collectPathMetrics, $collectStats, $handler) {
                $start = microtime(true);
                $response = $handler($request, $options);
                if ($response instanceof PromiseInterface) {
                    return $response->then(function ($result) use ($type, $start, $request, $collectPathMetrics, $collectStats) {
                        self::handleResponse(
                            $type,
                            $start,
                            $request->getHeaderLine('host'),
                            $request->getUri()->getPath(),
                            $collectPathMetrics,
                            $collectStats
                        );

                        return $result;
                    })->otherwise(function ($reason) use ($type, $start, $request, $collectPathMetrics, $collectStats) {
                        self::handleResponse(
                            $type,
                            $start,
                            $request->getHeaderLine('host'),
                            $request->getUri()->getPath(),
                            $collectPathMetrics,
                            $collectStats
                        );

                        return new RejectedPromise($reason);
                    });
                } else {
                    self::handleResponse(
                        $type,
                        $start,
                        $request->getHeaderLine('host'),
                        $request->getUri()->getPath(),
                        $collectPathMetrics,
                        $collectStats
                    );
                }

                return $response;
            };
        };
    }

    public static function handleResponse(
        string $type,
        $start,
        string $host,
        string $uriPath,
        bool $collectPathMetrics = false,
        bool $collectStats = false
    ): void {
        $end = microtime(true);
        $duration = $end - $start;

        /** @var LatencyProfiler $profiler */
        $profiler = resolve(LatencyProfiler::class);
        $profiler->addAsyncTimeQuant($type, $start, $end);

        $labels = [$host];
        Prometheus::update('http_client_seconds_total', $duration, $labels);
        Prometheus::update('http_client_requests_total', 1, $labels);

        if ($collectPathMetrics || $collectStats) {
            $normalizedPath = self::normalizePath($uriPath);
            $labels = [$host, $normalizedPath];

            if ($collectPathMetrics) {
                Prometheus::update('http_client_path_seconds_total', $duration, $labels);
                Prometheus::update('http_client_path_requests_total', 1, $labels);
            }

            if ($collectStats) {
                Prometheus::update('http_client_stats', $duration, $labels);
            }
        }
    }

    /**
     * Normalize URI path by replacing numeric segments with {id}
     *
     * @param string $path
     * @return string
     */
    public static function normalizePath(string $path): string
    {
        return preg_replace('#/(\d+)(?=/|$)#', '/{id}', $path);
    }
}
