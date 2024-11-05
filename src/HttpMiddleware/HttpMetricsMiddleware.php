<?php

namespace Ensi\LaravelMetrics\HttpMiddleware;

use Closure;
use Ensi\LaravelMetrics\LatencyProfiler;
use Ensi\LaravelPrometheus\Prometheus;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class HttpMetricsMiddleware
{
    protected int|float $duration;

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

        $this->duration = $endTime - $startTime;

        return $response;
    }

    public function terminate(Request $request, Response $response): void
    {
        $this->latencyProfiler->writeMetrics(Prometheus::bag(), $response->getStatusCode(), $this->duration);
    }
}
