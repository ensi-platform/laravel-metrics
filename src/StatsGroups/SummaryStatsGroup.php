<?php

namespace Ensi\LaravelMetrics\StatsGroups;

use Ensi\LaravelPrometheus\MetricsBag;

class SummaryStatsGroup extends StatsGroup
{
    public function registerMetrics(MetricsBag $metricsBag): void
    {
        $metricsBag->summary($this->groupMetricName($this->name), $this->options['time_window'], $this->options['quantiles']);
    }

    protected function updateMetrics(MetricsBag $metricsBag, float $totalTime): void
    {
        $metricsBag->update($this->groupMetricName($this->name), $totalTime);
    }
}
