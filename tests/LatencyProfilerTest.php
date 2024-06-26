<?php

namespace Ensi\LaravelMetrics\Tests;

use Ensi\LaravelMetrics\LatencyProfiler;
use Ensi\LaravelMetrics\Tests\TestCases\LatencyProfilerTestCase;
use Ensi\LaravelPrometheus\MetricsBag;
use Mockery\MockInterface;

uses(LatencyProfilerTestCase::class);

test('test register metrics without stats', function () {
    /** @var LatencyProfilerTestCase $this */

    config([
        'metrics.http_requests_stats_groups' => [],
    ]);

    /** @var MetricsBag|MockInterface $metricsBag */
    $metricsBag = $this->mock(MetricsBag::class);
    $this->assertDefaultMetricsRegistered($metricsBag);

    $latencyProfiler = new LatencyProfiler();
    $latencyProfiler->registerMetrics($metricsBag);
});

test('test register metrics with groups', function () {
    /** @var LatencyProfilerTestCase $this */

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
        ],
    ]);

    /** @var MetricsBag|MockInterface $metricsBag */
    $metricsBag = $this->mock(MetricsBag::class);
    $this->assertDefaultMetricsRegistered($metricsBag);

    $metricsBag->expects('summary')
        ->withArgs(['http_stats_s', 60, [0.5, 0.95]]);

    $metricsBag->expects('histogram')
        ->withArgs(['http_stats_h', [0.01, 0.02, 0.04, 0.08, 0.16]]);

    $latencyProfiler = new LatencyProfiler();
    $latencyProfiler->registerMetrics($metricsBag);
});

test('test simple total time', function () {
    /** @var LatencyProfilerTestCase $this */

    $latencyProfiler = new LatencyProfiler();

    /** @var MetricsBag|MockInterface $metricsBag */
    $metricsBag = $this->mock(MetricsBag::class);

    $metricsBag->expects('update')
        ->withArgs(['http_request_duration_seconds', 0.55, [200, 'php']]);

    $metricsBag->expects('update')
        ->withArgs(['http_requests_total', 1, [200]]);

    $latencyProfiler->writeMetrics($metricsBag, 200, 0.55);
});

test('test time quants', function (array $syncSpans, int $totalTime, int $spansTime, int $appTime) {
    /** @var LatencyProfilerTestCase $this */

    $latencyProfiler = new LatencyProfiler();

    /** @var MetricsBag|MockInterface $metricsBag */
    $metricsBag = $this->mock(MetricsBag::class);


    $metricsBag
        ->shouldReceive('update')
        ->once()
        ->with('http_request_duration_seconds', $spansTime, [200, 'db'])
        ->shouldReceive('update')
        ->once()
        ->with('http_request_duration_seconds', $appTime, [200, 'php'])
        ->shouldReceive('update')
        ->once()
        ->with('http_requests_total', 1, [200]);

    foreach ($syncSpans as $syncSpanTime) {
        $latencyProfiler->addTimeQuant('db', $syncSpanTime);
    }

    $latencyProfiler->writeMetrics($metricsBag, 200, $totalTime);
})->with([
    [[1], 10, 1, 9],
    [[1, 1], 10, 2, 8],
    [[2, 3, 4], 10, 9, 1],
]);

test('test async time quants', function (array $spans, float $total, float $spansTime, float $appTime) {
    /** @var LatencyProfilerTestCase $this */

    $latencyProfiler = new LatencyProfiler();

    /** @var MetricsBag|MockInterface $metricsBag */
    $metricsBag = $this->mock(MetricsBag::class);

    $metricsBag
        ->shouldReceive('update')
        ->once()
        ->withArgs(function ($name, $time, $labels) use ($spansTime) {
            return $name == 'http_request_duration_seconds'
                && abs($spansTime - $time) < 0.001
                && $labels == [200, 'http_client'];
        })
        ->shouldReceive('update')
        ->once()
        ->withArgs(function ($name, $time, $labels) use ($appTime) {
            return $name == 'http_request_duration_seconds'
                && abs($appTime - $time) < 0.001
                && $labels == [200, 'php'];
        })
        ->shouldReceive('update')
        ->once()
        ->with('http_requests_total', 1, [200]);

    foreach ($spans as [$spanStart, $spanEnd]) {
        $latencyProfiler->addAsyncTimeQuant('http_client', $spanStart, $spanEnd);
    }

    $latencyProfiler->writeMetrics($metricsBag, 200, $total);
})->with([
    [
        [
            [1670864385.148171, 1670864385.159712],
            [1670864385.162446, 1670864385.439255],
            [1670864385.442489, 1670864385.450273],
        ],
        1,
        0.29613423347473,
        0.7038,
    ],
    [[[1, 2]], 10, 1, 9],
    [[[1, 3], [2, 4]], 10, 3, 7],
    [[[1, 3], [3, 6]], 10, 5, 5],
    [[[1, 3], [5, 7]], 10, 4, 6],
]);

test('test ignore routes', function () {
    /** @var LatencyProfilerTestCase $this */

    /** @var MetricsBag|MockInterface $metricsBag */
    $metricsBag = $this->mock(MetricsBag::class);
    $metricsBag->shouldNotReceive('updateCounter');

    $this->mockCurrentRouteChecks([true]);

    $latencyProfiler = new LatencyProfiler();
    $latencyProfiler->writeMetrics($metricsBag, 200, 5);
});

test('test stats group', function () {
    /** @var LatencyProfilerTestCase $this */

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
        ],
    ]);

    /** @var MetricsBag|MockInterface $metricsBag */
    $metricsBag = $this->mock(MetricsBag::class);

    $metricsBag
        ->shouldReceive('update')
        ->once()
        ->with('http_request_duration_seconds', 5, [200, 'php'])
        ->shouldReceive('update')
        ->once()
        ->with('http_requests_total', 1, [200])
        ->shouldReceive('update')
        ->once()
        ->with('http_stats_s', 5)
        ->shouldReceive('update')
        ->once()
        ->with('http_stats_h', 5);

    $this->mockCurrentRouteChecks([false, true, true, false]);

    $latencyProfiler = new LatencyProfiler();
    $latencyProfiler->writeMetrics($metricsBag, 200, 5);
});
