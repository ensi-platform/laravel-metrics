<?php

namespace Ensi\LaravelMetrics\Kafka;

use Closure;
use Ensi\LaravelPrometheus\Prometheus;
use RdKafka\Message;
use Throwable;

class KafkaMetricsMiddleware
{
    public function handle(Message $message, Closure $next): mixed
    {
        $start = microtime(true);

        try {
            $response = $next($message);
            $this->writeMetrics($message, $start, KafkaResponseStatus::SUCCESS);
        } catch (Throwable $e) {
            $this->writeMetrics($message, $start, KafkaResponseStatus::FAILURE);

            throw $e;
        }

        return $response;
    }

    private function writeMetrics(Message $message, float $startKafka, KafkaResponseStatus $status): void
    {
        $duration = microtime(true) - $startKafka;
        $labels = KafkaLabels::extractFromMessage($message, $status->value);

        Prometheus::update('kafka_runs_total', 1, $labels);
        Prometheus::update('kafka_run_seconds_total', $duration, $labels);
    }
}
