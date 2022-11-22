<?php

namespace Madridianfox\LaravelMetrics\Tests;

use Illuminate\Routing\Route as CurrentRoute;
use Illuminate\Support\Facades\Route;
use Madridianfox\LaravelMetrics\LatencyProfiler;
use Madridianfox\LaravelPrometheus\MetricsBag;
use Mockery\MockInterface;

class LatencyProfilerTest extends TestCase
{
    private function assertDefaultMetricsRegistered(MetricsBag|MockInterface $metricsBag): void
    {
        $metricsBag->expects('declareCounter')
            ->withArgs(['http_requests_total', ['code']]);

        $metricsBag->expects('declareCounter')
            ->withArgs(['http_request_duration_seconds', ['code', 'type']]);
    }

    public function testRegisterMetricsWithoutStats(): void
    {
        config([
            'metrics.http_requests_stats_groups' => []
        ]);

        /** @var MetricsBag|MockInterface $metricsBag */
        $metricsBag = $this->mock(MetricsBag::class);
        $this->assertDefaultMetricsRegistered($metricsBag);

        $latencyProfiler = new LatencyProfiler();
        $latencyProfiler->registerMetrics($metricsBag);
    }

    public function testRegisterMetricsWithGroups(): void
    {
        config([
            'metrics.http_requests_stats_groups' => [
                's' => [
                    'type' => 'summary',
                    'route_names' => ['*'],
                    'time_window' => 60,
                    'quantiles' => [0.5, 0.95],
                ],
                'h' => [
                    'type' => 'histogram',
                    'route_names' => ['*'],
                    'buckets' => [0.01, 0.02, 0.04, 0.08, 0.16],
                ],
            ]
        ]);

        /** @var MetricsBag|MockInterface $metricsBag */
        $metricsBag = $this->mock(MetricsBag::class);
        $this->assertDefaultMetricsRegistered($metricsBag);

        $metricsBag->expects('declareSummary')
            ->withArgs(['http_stats_s', 60, [0.5, 0.95]]);

        $metricsBag->expects('declareHistogram')
            ->withArgs(['http_stats_h', [0.01, 0.02, 0.04, 0.08, 0.16]]);

        $latencyProfiler = new LatencyProfiler();
        $latencyProfiler->registerMetrics($metricsBag);
    }

    public function testSimpleTotalTime()
    {
        $latencyProfiler = new LatencyProfiler();

        /** @var MetricsBag|MockInterface $metricsBag */
        $metricsBag = $this->mock(MetricsBag::class);

        $metricsBag->expects('updateCounter')
            ->withArgs(['http_request_duration_seconds', [200, 'php'], 0.55]);

        $metricsBag->expects('updateCounter')
            ->withArgs(['http_requests_total', [200]]);

        $latencyProfiler->writeMetrics($metricsBag, 200, 0.55);
    }
    public function quantsProvider(): array
    {
        return [
            [[1], 10, 1, 9],
            [[1, 1], 10, 2, 8],
            [[2, 3, 4], 10, 9, 1],
        ];
    }

    /**
     * @dataProvider quantsProvider
     */
    public function testTimeQuants(array $syncSpans, int $totalTime, int $spansTime, int $appTime): void
    {
        $latencyProfiler = new LatencyProfiler();

        /** @var MetricsBag|MockInterface $metricsBag */
        $metricsBag = $this->mock(MetricsBag::class);


        $metricsBag
            ->shouldReceive('updateCounter')
            ->once()
            ->with('http_request_duration_seconds', [200, 'db'], $spansTime)

            ->shouldReceive('updateCounter')
            ->once()
            ->with('http_request_duration_seconds', [200, 'php'], $appTime)

            ->shouldReceive('updateCounter')
            ->once()
            ->with('http_requests_total', [200]);

        foreach ($syncSpans as $syncSpanTime) {
            $latencyProfiler->addTimeQuant('db', $syncSpanTime);
        }

        $latencyProfiler->writeMetrics($metricsBag, 200, $totalTime);
    }

    public function asyncQuantsProvider(): array
    {
        return [
            [[[1, 2]], 10, 1, 9],
            [[[1, 3], [2, 4]], 10, 3, 7],
            [[[1, 3], [3, 6]], 10, 5, 5],
            [[[1, 3], [5, 7]], 10, 4, 6],
        ];
    }

    /**
     * @dataProvider asyncQuantsProvider
     */
    public function testAsyncTimeQuants(array $spans, int $total, int $spansTime, int $appTime): void
    {
        $latencyProfiler = new LatencyProfiler();

        /** @var MetricsBag|MockInterface $metricsBag */
        $metricsBag = $this->mock(MetricsBag::class);

        $metricsBag
            ->shouldReceive('updateCounter')
            ->once()
            ->with('http_request_duration_seconds', [200, 'http_client'], $spansTime)

            ->shouldReceive('updateCounter')
            ->once()
            ->with('http_request_duration_seconds', [200, 'php'], $appTime)

            ->shouldReceive('updateCounter')
            ->once()
            ->with('http_requests_total', [200]);

        foreach ($spans as [$spanStart, $spanEnd]) {
            $latencyProfiler->addAsyncTimeQuant('http_client', $spanStart, $spanEnd);
        }

        $latencyProfiler->writeMetrics($metricsBag, 200, $total);
    }

    public function testIgnoreRoutes(): void
    {
        /** @var MetricsBag|MockInterface $metricsBag */
        $metricsBag = $this->mock(MetricsBag::class);
        $metricsBag->shouldNotReceive('updateCounter');

        $this->mockCurrentRouteChecks([true]);

        $latencyProfiler = new LatencyProfiler();
        $latencyProfiler->writeMetrics($metricsBag, 200, 5);
    }

    public function testStatsGroup(): void
    {
        config([
            'metrics.http_requests_stats_groups' => [
                's' => [
                    'type' => 'summary',
                    'route_names' => ['api'],
                    'time_window' => 60,
                    'quantiles' => [0.5, 0.95],
                ],
                'h' => [
                    'type' => 'histogram',
                    'route_names' => ['admin'],
                    'buckets' => [0.01, 0.02, 0.04, 0.08, 0.16],
                ],
                'h2' => [
                    'type' => 'histogram',
                    'route_names' => ['profile'],
                    'buckets' => [0.01, 0.02, 0.04, 0.08, 0.16],
                ],
            ]
        ]);

        /** @var MetricsBag|MockInterface $metricsBag */
        $metricsBag = $this->mock(MetricsBag::class);

        $metricsBag
            ->shouldReceive('updateCounter')
            ->once()
            ->with('http_request_duration_seconds', [200, 'php'], 5)

            ->shouldReceive('updateCounter')
            ->once()
            ->with('http_requests_total', [200])

            ->shouldReceive('updateSummary')
            ->once()
            ->with('http_stats_s', [], 5)

            ->shouldReceive('updateHistogram')
            ->once()
            ->with('http_stats_h', [], 5);

        $this->mockCurrentRouteChecks([false, true, true, false]);

        $latencyProfiler = new LatencyProfiler();
        $latencyProfiler->writeMetrics($metricsBag, 200, 5);
    }

    private function mockCurrentRouteChecks(array $checkResults)
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