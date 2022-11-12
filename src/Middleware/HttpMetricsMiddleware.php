<?php

namespace Madridianfox\LaravelMetrics\Middleware;

use Closure;
use Illuminate\Http\Response;
use Madridianfox\LaravelPrometheus\Prometheus;

class HttpMetricsMiddleware
{
    public function __construct(
        private readonly Prometheus $prometheus,
    ) {
    }

    public function handle($request, Closure $next)
    {
        /** @var Response $response */
        $response = $next($request);

        $this->prometheus->updateCounter('http_request', [
            $response->status(),
        ]);

        return $response ;
    }
}