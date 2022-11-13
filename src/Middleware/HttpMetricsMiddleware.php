<?php

namespace Madridianfox\LaravelMetrics\Middleware;

use Closure;
use Illuminate\Http\Response;
use Madridianfox\LaravelMetrics\LatencyProfiler;
use Madridianfox\LaravelPrometheus\PrometheusManager;

class HttpMetricsMiddleware
{
    public function __construct(
        private readonly PrometheusManager $prometheus,
        private readonly LatencyProfiler $latencyProfiler
    ) {
    }

    public function handle($request, Closure $next)
    {
        $this->prometheus->setCurrentContext('web');

        $startTime = microtime(true);
        /** @var Response $response */
        $response = $next($request);
        $endTime = microtime(true);

        $metricsBag = $this->prometheus->defaultBag('web');

        $metricsBag->updateCounter('http_request', [
            $response->status(),
        ]);

        $this->latencyProfiler->addTotalTime($endTime - $startTime);
        $this->latencyProfiler->writeMetrics($metricsBag, 'http_request_seconds', [
            $response->status(),
        ]);

        return $response ;
    }
}