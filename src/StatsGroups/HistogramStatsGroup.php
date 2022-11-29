<?php

namespace Madridianfox\LaravelMetrics\StatsGroups;

use Madridianfox\LaravelPrometheus\MetricsBag;

class HistogramStatsGroup extends StatsGroup
{
    public function registerMetrics(MetricsBag $metricsBag): void
    {
        $metricsBag->histogram($this->groupMetricName($this->name), $this->options['buckets']);
    }

    protected function updateMetrics(MetricsBag $metricsBag, float $totalTime): void
    {
        $metricsBag->update($this->groupMetricName($this->name), $totalTime);
    }
}