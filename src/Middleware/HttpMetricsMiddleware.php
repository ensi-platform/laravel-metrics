<?php

namespace Madridianfox\LaravelMetrics\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Madridianfox\LaravelMetrics\LatencyProfiler;
use Madridianfox\LaravelPrometheus\Prometheus;

class HttpMetricsMiddleware
{
    public function __construct(
        private readonly LatencyProfiler $latencyProfiler
    ) {
    }

    /**
     * @param Request $request
     * @param Closure $next
     * @return Response
     */
    public function handle($request, Closure $next)
    {
        Prometheus::setCurrentContext('web');

        $startTime = microtime(true);
        /** @var Response $response */
        $response = $next($request);
        $endTime = microtime(true);

        $metricsBag = Prometheus::defaultBag('web');

        $metricsBag->updateCounter('http_requests_total', [
            $response->status(),
        ]);

        $duration = $endTime - $startTime;

        $this->latencyProfiler->addTotalTime($duration);
        $this->latencyProfiler->writeMetrics($metricsBag, 'http_request_duration_seconds', [
            $response->status(),
        ]);

        return $response ;
    }
}