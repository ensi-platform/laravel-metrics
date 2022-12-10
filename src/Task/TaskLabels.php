<?php

namespace Ensi\LaravelMetrics\Task;

use Illuminate\Console\Application;
use Illuminate\Console\Scheduling\Event;

class TaskLabels
{
    public static function labelNames(): array
    {
        return ['command', 'status'];
    }

    public static function extractFromTask(Event $task): array
    {
        if ($task->command) {
            $command = self::formatArtisanCommand($task->command);
        } else {
            $command = self::formatJobCommand($task->description);
        }

        return [
            $command,
            $task->exitCode,
        ];
    }

    private static function formatArtisanCommand(string $command): string
    {
        return trim(str_replace([
            Application::phpBinary(),
            Application::artisanBinary(),
        ], "", $command));
    }

    private static function formatJobCommand(string $description): string
    {
        return str_replace("\\", "_", $description);
    }
}