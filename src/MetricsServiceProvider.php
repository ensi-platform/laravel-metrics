<?php

namespace Madridianfox\LaravelMetrics;

use Illuminate\Console\Events\ScheduledTaskFinished;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Madridianfox\LaravelMetrics\Job\JobLabels;
use Madridianfox\LaravelMetrics\Job\QueueSize;
use Madridianfox\LaravelMetrics\Labels\HttpRequestLabels;
use Madridianfox\LaravelMetrics\Task\TaskLabels;
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
        $this->registerOnDemandMetrics();
    }

    private function registerMetrics()
    {
        $metricsBag = Prometheus::bag();

        $metricsBag->counter('log_messages_count')
            ->middleware(HttpRequestLabels::class)
            ->labels(['level']);

        $metricsBag->counter('queue_job_runs_total')
            ->labels(JobLabels::labelNames());

        $metricsBag->counter('queue_job_failed_total')
            ->labels(JobLabels::labelNames());

        $metricsBag->counter('queue_job_run_seconds_total')
            ->labels(JobLabels::labelNames());

        $metricsBag->counter('task_runs_total')
            ->labels(TaskLabels::labelNames());

        $metricsBag->counter('task_run_seconds_total')
            ->labels(TaskLabels::labelNames());

        $metricsBag->counter('task_failed_total')
            ->labels(TaskLabels::labelNames());

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

        Event::listen(JobFailed::class, function (JobFailed $event) {
            Prometheus::update('queue_job_failed_total', 1, JobLabels::extractFromJob($event->job));
        });

        Event::listen(ScheduledTaskFinished::class, function (ScheduledTaskFinished $event) {
            Prometheus::update('task_runs_total', 1, TaskLabels::extractFromTask($event->task));
            Prometheus::update('task_run_seconds_total', $event->runtime, TaskLabels::extractFromTask($event->task));
        });
    }

    private function registerOnDemandMetrics(): void
    {
        Prometheus::addOnDemandMetric(QueueSize::class);
    }
}