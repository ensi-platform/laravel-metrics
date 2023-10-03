<?php

namespace Ensi\LaravelMetrics\Tests;

use Ensi\LaravelMetrics\Job\JobMiddleware;
use Illuminate\Console\Events\CommandFinished;
use Illuminate\Console\Events\ScheduledTaskFinished;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobQueued;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Ensi\LaravelMetrics\LatencyProfiler;
use Ensi\LaravelMetrics\MetricsServiceProvider;
use Ensi\LaravelPrometheus\Metrics\Counter;
use Ensi\LaravelPrometheus\MetricsBag;
use Ensi\LaravelPrometheus\Prometheus;
use Mockery;
use Mockery\MockInterface;

class ServiceProviderTest extends TestCase
{
    public function testRegisterLatencyProfiler(): void
    {
        $this->assertInstanceOf(LatencyProfiler::class, resolve(LatencyProfiler::class));
    }

    public function testRegisterMetrics(): void
    {
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
    }

    public function testRegisterEventListeners(): void
    {
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
    }
}