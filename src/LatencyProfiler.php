<?php

namespace Madridianfox\LaravelMetrics;

use Madridianfox\LaravelPrometheus\MetricsBag;

class LatencyProfiler
{
    private array $timeQuants = [];
    private array $asyncTimeQuants = [];
    private float $totalTime = 0;

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

    public function addTotalTime(float $totalTime): void
    {
        $this->totalTime = $totalTime;
    }

    public function profile(string $type, \Closure $fn): mixed
    {
        $startTime = microtime(true);
        $result = $fn();
        $endTime = microtime(true);
        $this->addTimeQuant($type, $endTime - $startTime);

        return $result;
    }

    public function flushData(): void
    {
        $this->timeQuants = [];
        $this->asyncTimeQuants = [];
        $this->totalTime = 0;
    }

    public function writeMetrics(MetricsBag $prometheus, string $name, array $labels = []): void
    {
        $excludedDuration = 0;

        foreach ($this->timeQuants as $type => $duration) {
            $prometheus->updateCounter($name, array_merge($labels, [$type]), $duration);
            $excludedDuration += $duration;
        }

        foreach ($this->asyncTimeQuants as $type => $intervals) {
            $duration = $this->overallIntervalsDuration($intervals);
            $prometheus->updateCounter($name, array_merge($labels, [$type]), $duration);
            $excludedDuration += $duration;
        }

        $appDuration = $this->totalTime - $excludedDuration;
        $prometheus->updateCounter($name, array_merge($labels, ['php']), $appDuration);

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