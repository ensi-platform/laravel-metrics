<?php

namespace Ensi\LaravelMetrics\Job;

use Illuminate\Support\Facades\Queue;
use Ensi\LaravelPrometheus\MetricsBag;
use Ensi\LaravelPrometheus\OnDemandMetrics\OnDemandMetric;

class QueueSize implements OnDemandMetric
{
    public function register(MetricsBag $metricsBag): void
    {
        $metricsBag->gauge('queue_size')
            ->labels(['queue']);
    }

    public function update(MetricsBag $metricsBag): void
    {
        foreach (config('metrics.watch_queues') as $queueName) {
            $jobsCount = Queue::size($queueName);
            $safeQueueName = trim($queueName, '{}');

            $metricsBag->update('queue_size', $jobsCount, [$safeQueueName]);
        }
    }
}