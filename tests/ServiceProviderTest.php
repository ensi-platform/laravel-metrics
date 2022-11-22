<?php

namespace Madridianfox\LaravelMetrics\Tests;

use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Event;
use Madridianfox\LaravelMetrics\LatencyProfiler;
use Madridianfox\LaravelMetrics\MetricsServiceProvider;
use Madridianfox\LaravelPrometheus\MetricsBag;
use Madridianfox\LaravelPrometheus\Prometheus;
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
        $metricsBag->expects('addLabelMiddleware');
        $metricsBag->expects('declareCounter');

        Prometheus::expects('bag')
            ->andReturn($metricsBag);

        $serviceProvider = new MetricsServiceProvider($this->app);
        $serviceProvider->boot();
    }

    public function testRegisterEventListeners(): void
    {
        Event::expects('listen')
            ->withArgs([QueryExecuted::class, Mockery::any()]);

        Event::expects('listen')
            ->withArgs([MessageLogged::class, Mockery::any()]);

        $serviceProvider = new MetricsServiceProvider($this->app);
        $serviceProvider->boot();
    }
}