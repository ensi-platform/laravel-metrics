<?php

namespace Ensi\LaravelMetrics\HttpMiddleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Ensi\LaravelMetrics\LatencyProfiler;
use Ensi\LaravelPrometheus\Prometheus;

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
        $this->latencyProfiler->writeMetrics(Prometheus::bag(), $response->getStatusCode(), $duration);

        return $response;
    }
}