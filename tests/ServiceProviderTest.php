<?php

namespace Ensi\LaravelMetrics\Tests;

use Ensi\LaravelMetrics\Job\JobMiddleware;
use Ensi\LaravelMetrics\LatencyProfiler;
use Ensi\LaravelMetrics\MetricsServiceProvider;
use Ensi\LaravelPrometheus\Metrics\Counter;
use Ensi\LaravelPrometheus\MetricsBag;
use Ensi\LaravelPrometheus\Prometheus;
use Illuminate\Console\Events\CommandFinished;
use Illuminate\Console\Events\ScheduledTaskFinished;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobQueued;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Mockery;
use Mockery\MockInterface;

use function PHPUnit\Framework\assertInstanceOf;

uses(TestCase::class);

test('test register latency profiler', function () {
    /** @var TestCase $this */

    assertInstanceOf(LatencyProfiler::class, resolve(LatencyProfiler::class));
});

test('test register metrics', function () {
    /** @var TestCase $this */

    /** @var LatencyProfiler|MockInterface $latencyProfiler */
    $latencyProfiler = $this->mock(LatencyProfiler::class);
    $latencyProfiler->expects('registerMetrics');
    app()->instance(LatencyProfiler::class, $latencyProfiler);

    /** @var MetricsBag|MockInterface $metricsBag */
    $metricsBag = $this->mock(MetricsBag::class);
    $metricsBag->expects('counter')
        ->times(14)
        ->andReturnUsing(fn () => new Counter($metricsBag, 'n'));

    Prometheus::expects('bag')
        ->andReturn($metricsBag);

    Prometheus::expects('addOnDemandMetric')->times(2);

    $serviceProvider = new MetricsServiceProvider($this->app);
    $serviceProvider->boot();
});

test('test register event listeners', function () {
    /** @var TestCase $this */

    Event::expects('listen')
        ->withArgs([QueryExecuted::class, Mockery::any()]);

    Event::expects('listen')
        ->withArgs([MessageLogged::class, Mockery::any()]);

    Event::expects('listen')
        ->withArgs([JobFailed::class, Mockery::any()]);

    Event::expects('listen')
        ->withArgs([JobQueued::class, Mockery::any()]);

    Bus::shouldReceive('pipeThrough')
        ->once()
        ->withArgs([[JobMiddleware::class]]);

    Event::expects('listen')
        ->withArgs([ScheduledTaskFinished::class, Mockery::any()]);

    Event::expects('listen')
        ->withArgs([CommandFinished::class, Mockery::any()]);

    $serviceProvider = new MetricsServiceProvider($this->app);
    $serviceProvider->boot();
});
