<?php

namespace Ensi\LaravelMetrics\Command;

use Illuminate\Console\Events\CommandFinished;

class CommandLabels
{
    public static function labelNames(): array
    {
        return ['command', 'status'];
    }

    public static function extractFromTask(CommandFinished $event): array
    {
        return [
            $event->command,
            $event->exitCode,
        ];
    }
}
