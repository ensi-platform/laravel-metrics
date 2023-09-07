<?php

namespace Ensi\LaravelMetrics\Kafka;

use RdKafka\Message;

class KafkaLabels
{
    public static function labelNames(): array
    {
        return ['kafka'];
    }

    public static function extractFromMessage(Message $message): array
    {
        return [
            self::extractEntrypointFromInput(),
        ];
    }

    protected static function extractEntrypointFromInput(): string
    {
        $argv = $_SERVER['argv'] ?? [];
        $argvWithoutOptions = array_filter($argv, fn ($arg) => !str_starts_with($arg, '-'));

        return count($argvWithoutOptions) ? end($argvWithoutOptions) : 'default';
    }
}
