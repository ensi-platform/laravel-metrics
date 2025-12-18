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
        $duration = $end - $start;

        /** @var LatencyProfiler $profiler */
        $profiler = resolve(LatencyProfiler::class);
        $profiler->addAsyncTimeQuant($type, $start, $end);

        $labels = [$host];
        Prometheus::update('http_client_seconds_total', $duration, $labels);
        Prometheus::update('http_client_requests_total', 1, $labels);

        // Collect endpoint avg time if enabled
        $endpointAvgConfig = config('metrics.http_client_endpoint_avg_time', []);
        if ($endpointAvgConfig['enabled'] ?? false) {
            $domains = $endpointAvgConfig['domains'] ?? [];
            if (in_array('*', $domains) || in_array($host, $domains)) {
                $path = preg_replace('#/(\d+)(?=/|$)#', '/{id}', $uriPath);
                $endpointLabels = [$host, $path];
                Prometheus::update('http_client_endpoint_seconds_total', $duration, $endpointLabels);
                Prometheus::update('http_client_endpoint_requests_total', 1, $endpointLabels);
            }
        }

        // Collect percentiles if enabled
        $percentilesConfig = config('metrics.http_client_percentiles', []);
        if ($percentilesConfig['enabled'] ?? false) {
            $domains = $percentilesConfig['domains'] ?? [];
            $endpoints = $percentilesConfig['endpoints'] ?? [];
            $shouldCollect = false;
            if (in_array('*', $domains) || in_array($host, $domains)) {
                $shouldCollect = true;
            } elseif (!empty($endpoints)) {
                $fullEndpoint = $host . $uriPath;
                foreach ($endpoints as $endpoint) {
                    if (str_contains($fullEndpoint, $endpoint)) {
                        $shouldCollect = true;
                        break;
                    }
                }
            }
            if ($shouldCollect) {
                $metricName = 'http_client_percentiles';
                $path = preg_replace('#/(\d+)(?=/|$)#', '/{id}', $uriPath);
                Prometheus::update($metricName, $duration, [$host, $path]);
            }
        }
    }
}
