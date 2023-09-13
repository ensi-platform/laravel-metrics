<?php

namespace Ensi\LaravelMetrics\Command;

use Ensi\LaravelMetrics\Helper;
use Ensi\LaravelPrometheus\Prometheus;
use Illuminate\Console\Events\CommandFinished;

class CommandMetrics
{
    public static function write(CommandFinished $event): void
    {
        if (self::needToIgnoreCommand($event->command)) {
            return;
        }

        $labels = CommandLabels::extractFromTask($event);

        Prometheus::update('command_runs_total', 1, $labels);
        Prometheus::update('command_run_seconds_total', Helper::duration(), $labels);
    }

    protected static function needToIgnoreCommand(?string $command): bool
    {
        if (!isset($command)) {
            return true;
        }

        $commands = config('metrics.ignore_commands', []);

        return in_array($command, $commands);
    }
}
