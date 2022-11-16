<?php

namespace Madridianfox\LaravelMetrics;

use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\Event;
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
        $this->registerMetrics();
        $this->registerEventListeners();
    }

    private function registerMetrics()
    {
        /** @var PrometheusManager $prometheus */
        $prometheus = resolve(PrometheusManager::class);
        $metricsBag = $prometheus->defaultBag('web');

        $metricsBag->addLabelProcessor(HttpRequestLabelProvider::class);

        $metricsBag->declareCounter('http_requests_total', ['code']);
        $metricsBag->declareCounter('http_request_duration_seconds', ['code', 'type']);
    }

    private function registerEventListeners()
    {
        Event::listen(QueryExecuted::class, function (QueryExecuted $event) {
            /** @var LatencyProfiler $profiler */
            $profiler = resolve(LatencyProfiler::class);
            $profiler->addTimeQuant($event->connectionName, $event->time / 1000);
        });
    }
}