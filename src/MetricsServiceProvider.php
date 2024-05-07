<?php

namespace Ensi\LaravelMetrics;

use Ensi\LaravelMetrics\Command\CommandLabels;
use Ensi\LaravelMetrics\Command\CommandMetrics;
use Ensi\LaravelMetrics\Job\JobLabels;
use Ensi\LaravelMetrics\Job\JobMiddleware;
use Ensi\LaravelMetrics\Job\QueueSize;
use Ensi\LaravelMetrics\Kafka\KafkaLabels;
use Ensi\LaravelMetrics\Labels\HttpRequestLabels;
use Ensi\LaravelMetrics\Task\TaskLabels;
use Ensi\LaravelMetrics\Workers\WorkerUsage;
use Ensi\LaravelPrometheus\Prometheus;
use Illuminate\Console\Events\CommandFinished;
use Illuminate\Console\Events\ScheduledTaskFinished;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobQueued;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

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

        $metricsBag->counter('queue_job_dispatched_total')
            ->labels(JobLabels::labelNames());

        $metricsBag->counter('task_runs_total')
            ->labels(TaskLabels::labelNames());

        $metricsBag->counter('task_run_seconds_total')
            ->labels(TaskLabels::labelNames());

        $metricsBag->counter('task_failed_total')
            ->labels(TaskLabels::labelNames());

        $metricsBag->counter('http_client_seconds_total')
            ->labels(['host']);

        $metricsBag->counter('http_client_requests_total')
            ->labels(['host']);

        $metricsBag->counter('kafka_runs_total')
            ->labels(KafkaLabels::labelNames());

        $metricsBag->counter('kafka_run_seconds_total')
            ->labels(KafkaLabels::labelNames());

        $metricsBag->counter('command_runs_total')
            ->labels(CommandLabels::labelNames());

        $metricsBag->counter('command_run_seconds_total')
            ->labels(CommandLabels::labelNames());

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

        Event::listen(JobQueued::class, function (JobQueued $event) {
            Prometheus::update('queue_job_dispatched_total', 1, JobLabels::extractFromJob($event->job));
        });

        Bus::pipeThrough([
            JobMiddleware::class,
        ]);

        Event::listen(ScheduledTaskFinished::class, function (ScheduledTaskFinished $event) {
            Prometheus::update('task_runs_total', 1, TaskLabels::extractFromTask($event->task));
            Prometheus::update('task_run_seconds_total', $event->runtime, TaskLabels::extractFromTask($event->task));
        });

        Event::listen(CommandFinished::class, function (CommandFinished $event) {
            CommandMetrics::write($event);
        });
    }

    private function registerOnDemandMetrics(): void
    {
        Prometheus::addOnDemandMetric(QueueSize::class);
        Prometheus::addOnDemandMetric(WorkerUsage::class);
    }
}
