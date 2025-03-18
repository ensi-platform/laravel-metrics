<?php

namespace Ensi\LaravelMetrics\Job;

use Ensi\LaravelPrometheus\Prometheus;

class JobMiddleware
{
    public function handle($job, $next)
    {
        $labels = JobLabels::extractFromJob($job);

        $start = microtime(true);
        $next($job);
        $duration = microtime(true) - $start;

        app()->terminating(fn () => Prometheus::update('queue_job_runs_total', 1, $labels));
        app()->terminating(fn () => Prometheus::update('queue_job_run_seconds_total', $duration, $labels));
    }
}
