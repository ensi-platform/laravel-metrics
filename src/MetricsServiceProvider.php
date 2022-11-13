<?php

namespace Madridianfox\LaravelMetrics;

use Illuminate\Support\ServiceProvider;
use Madridianfox\LaravelMetrics\LabelProcessors\HttpRequestLabelProvider;
use Madridianfox\LaravelPrometheus\PrometheusManager;

class MetricsServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(LatencyProfiler::class);
    }

    public function boot()
    {
        /** @var PrometheusManager $prometheus */
        $prometheus = resolve(PrometheusManager::class);
        $metricsBag = $prometheus->defaultBag('web');

        $metricsBag->addLabelProcessor(HttpRequestLabelProvider::class);

        $metricsBag->declareCounter('http_request', ['code']);
        $metricsBag->declareCounter('http_request_seconds', ['code', 'type']);
    }
}