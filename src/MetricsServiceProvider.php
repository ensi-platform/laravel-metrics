<?php

namespace Madridianfox\LaravelMetrics;

use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Madridianfox\LaravelMetrics\LabelMiddlewares\HttpRequestLabelMiddleware;
use Madridianfox\LaravelPrometheus\Prometheus;

class MetricsServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(LatencyProfiler::class);
        $this->mergeConfigFrom(__DIR__.'/../config/metrics.php', 'metrics');
    }

    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/metrics.php' => config_path('metrics.php'),
            ], 'metrics-config');
        }

        $this->registerMetrics();
        $this->registerEventListeners();
    }

    private function registerMetrics()
    {
        $metricsBag = Prometheus::bag();

        $metricsBag->counter('log_messages_count')
            ->middleware(HttpRequestLabelMiddleware::class)
            ->labels(['level']);

        resolve(LatencyProfiler::class)->registerMetrics($metricsBag);
    }

    private function registerEventListeners()
    {
        Event::listen(QueryExecuted::class, function (QueryExecuted $event) {
            /** @var LatencyProfiler $profiler */
            $profiler = resolve(LatencyProfiler::class);
            $profiler->addTimeQuant($event->connectionName, $event->time / 1000);
        });

        Event::listen(MessageLogged::class, function (MessageLogged $event) {
            Prometheus::update('log_messages_count', 1, [$event->level]);
        });
    }
}