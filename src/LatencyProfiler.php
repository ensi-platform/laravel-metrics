<?php

namespace Ensi\LaravelMetrics;

use Illuminate\Support\Facades\Route;
use Ensi\LaravelMetrics\Labels\HttpRequestLabels;
use Ensi\LaravelMetrics\StatsGroups\StatsGroup;
use Ensi\LaravelPrometheus\MetricsBag;

class LatencyProfiler
{
    private array $timeQuants = [];
    private array $asyncTimeQuants = [];
    /** @var array<StatsGroup> */
    private array $statsGroups = [];

    public function __construct()
    {
        foreach (config('metrics.http_requests_stats_groups') as $groupName => $options) {
            $this->statsGroups[] = StatsGroup::createByType($groupName, $options);
        }
    }

    public function registerMetrics(MetricsBag $metricsBag): void
    {
        $metricsBag->counter('http_requests_total')
            ->middleware(HttpRequestLabels::class)
            ->labels(['code']);
        $metricsBag->counter('http_request_duration_seconds')
            ->middleware(HttpRequestLabels::class)
            ->labels(['code', 'type']);

        foreach ($this->statsGroups as $statsGroup) {
            $statsGroup->registerMetrics($metricsBag);
        }
    }

    public function addTimeQuant(string $type, float $duration): void
    {
        if (!array_key_exists($type, $this->timeQuants)) {
            $this->timeQuants[$type] = 0;
        }

        $this->timeQuants[$type] += $duration;
    }

    public function addAsyncTimeQuant(string $type, float $startMicrotime, float $endMicrotime): void
    {
        $this->asyncTimeQuants[$type][] = [$startMicrotime, $endMicrotime];
    }

    public function flushData(): void
    {
        $this->timeQuants = [];
        $this->asyncTimeQuants = [];
    }

    public function writeMetrics(MetricsBag $metricsBag, int $responseCode, float $totalTime): void
    {
        if (Route::current()?->named(config('metrics.ignore_routes'))) {
            return;
        }

        $labels = [$responseCode];
        $excludedDuration = 0;

        foreach ($this->timeQuants as $type => $duration) {
            $metricsBag->update('http_request_duration_seconds', $duration, array_merge($labels, [$type]));
            $excludedDuration += $duration;
        }

        foreach ($this->asyncTimeQuants as $type => $intervals) {
            $duration = $this->overallIntervalsDuration($intervals);
            $metricsBag->update('http_request_duration_seconds', $duration, array_merge($labels, [$type]));
            $excludedDuration += $duration;
        }

        $appDuration = $totalTime - $excludedDuration;
        $metricsBag->update('http_request_duration_seconds', $appDuration, array_merge($labels, ['php']));

        $metricsBag->update('http_requests_total', 1, $labels);


        foreach ($this->statsGroups as $statsGroup) {
            $statsGroup->checkAndUpdateMetric($metricsBag, $totalTime);
        }

        $this->flushData();
    }

    private function overallIntervalsDuration(array $intervals): float
    {
        usort($intervals, function (array $a, array $b) {
            return $a[0] <=> $b[0];
        });

        $stack = [
            array_shift($intervals),
        ];

        foreach ($intervals as $nextInterval) {
            $currentInterval = end($stack);
            if ($currentInterval[0] <= $nextInterval[0] && $nextInterval[0] <= $currentInterval[1]) {
                $currentIntervalIndex = count($stack) - 1;
                $stack[$currentIntervalIndex][1] = max($currentInterval[1], $nextInterval[1]);
            } else {
                $stack[] = $nextInterval;
            }
        }

        return array_reduce($stack, function ($sum, $interval) {
            return $sum + ($interval[1] - $interval[0]);
        }, 0);
    }
}