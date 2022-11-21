<?php

namespace Madridianfox\LaravelMetrics\StatsGroups;

use Madridianfox\LaravelPrometheus\MetricsBag;

class HistogramStatsGroup extends StatsGroup
{
    public function registerMetrics(MetricsBag $metricsBag): void
    {
        $metricsBag->declareHistogram($this->groupMetricName($this->name), $this->options['buckets']);
    }

    protected function updateMetrics(MetricsBag $metricsBag, float $totalTime): void
    {
        $metricsBag->updateHistogram($this->groupMetricName($this->name), [], $totalTime);
    }
}