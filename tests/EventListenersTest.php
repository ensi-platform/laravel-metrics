<?php

namespace Ensi\LaravelMetrics\Tests;

use Ensi\LaravelMetrics\Job\JobLabels;
use Ensi\LaravelMetrics\LatencyProfiler;
use Ensi\LaravelMetrics\Tests\Factories\CustomJob;
use Ensi\LaravelPrometheus\Prometheus;
use Illuminate\Console\Events\CommandFinished;
use Illuminate\Database\Connection;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Queue\Events\JobQueued;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Mockery\MockInterface;
use ReflectionMethod;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\ConsoleOutput;

class EventListenersTest extends TestCase
{
    public function testListenDatabaseQueries(): void
    {
        /** @var LatencyProfiler|MockInterface $latenctyProfiler */
        $latencyProfiler = $this->mock(LatencyProfiler::class);
        $latencyProfiler->expects('addTimeQuant');

        /** @var Connection $connection */
        $connection = DB::connection();

        Event::dispatch(new QueryExecuted("example", [], 1, $connection));
    }

    public function testListenMessageLogged(): void
    {
        Prometheus::shouldReceive('update')
            ->once()
            ->withArgs(['log_messages_count', 1, ['info']]);
        logger()->info("hello");
    }

    public function testListenMessageJobQueued(): void
    {
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
    }

    public function testListenMessageJobProcessed(): void
    {
        $job = CustomJob::factory();
        $labels = JobLabels::extractFromJob($job);

        Prometheus::shouldReceive('update')
            ->once()
            ->withArgs(['queue_job_runs_total', 1, $labels]);

        Prometheus::shouldReceive('update')
            ->once()
            ->withArgs(function (string $name, float $value, array $labelValues) use ($labels) {
                self::assertEqualsCanonicalizing($labelValues, $labels);

                self::assertEquals($name, 'queue_job_run_seconds_total');

                return true;
            });

        Bus::dispatch($job);
    }

    public function testListenMessageCommandFinished(): void
    {
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
                self::assertEqualsCanonicalizing($labelValues, $labels);

                self::assertEquals($name, 'command_run_seconds_total');

                return true;
            });

        Event::dispatch(new CommandFinished($command, $input, $output, $exitCode));
    }

    public function testListenMessageCommandFinishedSkip(): void
    {
        $skipCommand = 'command:skip';
        config()->set('metrics.ignore_commands', [$skipCommand]);

        $input = new StringInput('');
        $output = new ConsoleOutput();
        $exitCode = 0;

        Prometheus::shouldReceive('update')->never();
        Prometheus::shouldReceive('update')->never();

        Event::dispatch(new CommandFinished($skipCommand, $input, $output, $exitCode));
    }
}
