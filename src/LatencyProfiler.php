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

    private function overallIntervalsDuration(mixed $intervals): float
    {
        // todo
        return 0;
    }
}