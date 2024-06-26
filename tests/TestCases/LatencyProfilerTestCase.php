<?php

namespace Ensi\LaravelMetrics\Tests\TestCases;

use Ensi\LaravelMetrics\Tests\TestCase;
use Ensi\LaravelPrometheus\Metrics\Counter;
use Ensi\LaravelPrometheus\MetricsBag;
use Illuminate\Routing\Route as CurrentRoute;
use Illuminate\Support\Facades\Route;
use Mockery\MockInterface;

class LatencyProfilerTestCase extends TestCase
{
    protected function assertDefaultMetricsRegistered(MetricsBag|MockInterface $metricsBag): void
    {
        $counter1 = tap($this->mock(Counter::class), function (MockInterface $counter) {
            $counter->shouldReceive('labels')
                ->withArgs([['code']])
                ->andReturnSelf();
            $counter->shouldReceive('middleware')
                ->andReturnSelf();
        });
        $metricsBag->expects('counter')
            ->withArgs(['http_requests_total'])
            ->andReturn($counter1);

        $counter2 = tap($this->mock(Counter::class), function (MockInterface $counter) {
            $counter->shouldReceive('labels')
                ->withArgs([['code', 'type']])
                ->andReturnSelf();
            $counter->shouldReceive('middleware')
                ->andReturnSelf();
        });
        $metricsBag->expects('counter')
            ->withArgs(['http_request_duration_seconds'])
            ->andReturn($counter2);
    }

    protected function mockCurrentRouteChecks(array $checkResults): void
    {
        $currentRoute = $this->mock(CurrentRoute::class);
        foreach ($checkResults as $result) {
            $currentRoute
                ->shouldReceive('named')
                ->once()
                ->andReturn($result);
        }

        Route::shouldReceive('current')
            ->andReturn($currentRoute);
    }
}
