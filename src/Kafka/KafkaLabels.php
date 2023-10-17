<?php

namespace Ensi\LaravelMetrics\Kafka;

use RdKafka\Message;

class KafkaLabels
{
    public static function labelNames(): array
    {
        return ['kafka', 'status'];
    }

    public static function extractFromMessage(Message $message, int $status): array
    {
        return [
            self::extractEntrypointFromInput(),
            $status,
        ];
    }

    protected static function extractEntrypointFromInput(): string
    {
        $argv = $_SERVER['argv'] ?? [];
        $argvWithoutOptions = array_filter($argv, fn ($arg) => !str_starts_with($arg, '-'));

        return count($argvWithoutOptions) ? end($argvWithoutOptions) : 'default';
    }
}
