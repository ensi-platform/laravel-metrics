<?php

namespace Madridianfox\LaravelMetrics;

use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Madridianfox\LaravelMetrics\LabelProcessors\HttpRequestLabelProvider;
use Madridianfox\LaravelPrometheus\Prometheus;
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

        $metricsBag->declareCounter('log_messages_count', ['level']);
    }

    private function registerEventListeners()
    {
        Event::listen(QueryExecuted::class, function (QueryExecuted $event) {
            /** @var LatencyProfiler $profiler */
            $profiler = resolve(LatencyProfiler::class);
            $profiler->addTimeQuant($event->connectionName, $event->time / 1000);
        });

        Event::listen(MessageLogged::class, function (MessageLogged $event) {
            Prometheus::updateCounter('log_messages_count', [$event->level]);
        });
    }
}