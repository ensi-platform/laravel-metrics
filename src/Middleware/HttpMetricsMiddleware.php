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
        $startTime = microtime(true);
        /** @var Response $response */
        $response = $next($request);
        $endTime = microtime(true);

        $duration = $endTime - $startTime;
        $this->latencyProfiler->writeMetrics(Prometheus::bag(), $response->status(), $duration);

        return $response;
    }
}