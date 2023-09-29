<?php

namespace Ensi\LaravelMetrics\Job;

use Illuminate\Bus\Queueable;

class JobLabels
{
    public static function labelNames(): array
    {
        return ['connection', 'queue', 'job_name'];
    }

    public static function extractFromJob($job): array
    {
        /** @var Queueable $job */
        $connection = $job->connection ?? config('queue.default');
        $queue = $job->queue ?? config("queue.connections.{$connection}.queue");

        return [
            $connection,
            $queue,
            str_replace("\\", "_", $job::class),
        ];
    }
}