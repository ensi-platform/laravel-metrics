<?php

namespace Ensi\LaravelMetrics\Kafka;

use Closure;
use Ensi\LaravelPrometheus\Prometheus;
use RdKafka\Message;

class KafkaMetricsMiddleware
{
    public function handle(Message $message, Closure $next): mixed
    {
        $labels = KafkaLabels::extractFromMessage($message);

        $start = microtime(true);
        $response = $next($message);
        $duration = microtime(true) - $start;

        Prometheus::update('kafka_runs_total', 1, $labels);
        Prometheus::update('kafka_run_seconds_total', $duration, $labels);

        return $response;
    }
}