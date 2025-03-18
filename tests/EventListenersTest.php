<?php

namespace Ensi\LaravelMetrics\Tests;

use Ensi\LaravelMetrics\Job\JobLabels;
use Ensi\LaravelMetrics\LatencyProfiler;
use Ensi\LaravelMetrics\Tests\Factories\CustomJob;
use Ensi\LaravelPrometheus\Prometheus;
use Illuminate\Console\Events\CommandFinished;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Queue\Events\JobQueued;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Mockery\MockInterface;

use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertEqualsCanonicalizing;

use ReflectionMethod;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\ConsoleOutput;

uses(TestCase::class);

test('test listen database queries', function () {
    /** @var TestCase $this */

    /** @var LatencyProfiler|MockInterface $latenctyProfiler */
    $latencyProfiler = $this->mock(LatencyProfiler::class);
    $latencyProfiler->expects('addTimeQuant');

    $connection = DB::connection();

    Event::dispatch(new QueryExecuted("example", [], 1, $connection));
});

test('test listen message logged', function () {
    /** @var TestCase $this */

    Prometheus::shouldReceive('update')
        ->once()
        ->withArgs(['log_messages_count', 1, ['info']]);

    logger()->info("hello");

    app()->terminate();
});

test('test listen message job queued', function () {
    /** @var TestCase $this */

    $job = CustomJob::factory();
    $labels = JobLabels::extractFromJob($job);

    Prometheus::shouldReceive('update')
        ->once()
        ->withArgs(['queue_job_dispatched_total', 1, $labels]);

    // JobQueued signature have incompatible changes in LARAVEL 11.x
    $arr = [];
    foreach ((new ReflectionMethod(JobQueued::class, '__construct'))->getParameters() as $param) {
        $arr[$param->getName()] = match ($param->getName()) {
            'connectionName' => $job->connection,
            'id' => 1,
            'job' => $job,
            // Laravel 10
            'payload' => '',
            // Laravel 11
            'queue' => 'default',
            'delay' => null,
        };
    }
    Event::dispatch(new JobQueued(...$arr));

    app()->terminate();
});

test('test listen message job processed', function () {
    /** @var TestCase $this */

    $job = CustomJob::factory();
    $labels = JobLabels::extractFromJob($job);

    Prometheus::shouldReceive('update')
        ->once()
        ->withArgs(['queue_job_runs_total', 1, $labels]);

    Prometheus::shouldReceive('update')
        ->once()
        ->withArgs(function (string $name, float $value, array $labelValues) use ($labels) {
            assertEqualsCanonicalizing($labelValues, $labels);

            assertEquals($name, 'queue_job_run_seconds_total');

            return true;
        });

    Bus::dispatch($job);

    app()->terminate();
});

test('test listen message command finished', function () {
    /** @var TestCase $this */

    $command = 'test:command';
    $input = new StringInput('');
    $output = new ConsoleOutput();
    $exitCode = 0;

    $labels = [$command, $exitCode];

    Prometheus::shouldReceive('update')
        ->once()
        ->withArgs(['command_runs_total', 1, $labels]);

    Prometheus::shouldReceive('update')
        ->once()
        ->withArgs(function (string $name, float $value, array $labelValues) use ($labels) {
            assertEqualsCanonicalizing($labelValues, $labels);

            assertEquals($name, 'command_run_seconds_total');

            return true;
        });

    Event::dispatch(new CommandFinished($command, $input, $output, $exitCode));

    app()->terminate();
});

test('test listen message command finished skip', function () {
    /** @var TestCase $this */

    $skipCommand = 'command:skip';
    config()->set('metrics.ignore_commands', [$skipCommand]);

    $input = new StringInput('');
    $output = new ConsoleOutput();
    $exitCode = 0;

    Prometheus::shouldReceive('update')->never();
    Prometheus::shouldReceive('update')->never();

    Event::dispatch(new CommandFinished($skipCommand, $input, $output, $exitCode));
});
