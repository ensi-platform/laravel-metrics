<?php

namespace Ensi\LaravelMetrics\StatsGroups;

use Illuminate\Support\Facades\Route;
use Ensi\LaravelPrometheus\MetricsBag;

abstract class StatsGroup
{
    public abstract function registerMetrics(MetricsBag $metricsBag): void;
    protected abstract function updateMetrics(MetricsBag $metricsBag, float $totalTime): void;

    public function __construct(
        protected string $name,
        protected array $options
    ) {
    }

    public static function createByType(string $name, array $options): static
    {
        return match ($options['type']) {
            'summary' => new SummaryStatsGroup($name, $options),
            'histogram' => new HistogramStatsGroup($name, $options),
        };
    }

    public function checkAndUpdateMetric(MetricsBag $metricsBag, float $totalTime): void
    {
        if (Route::current()?->named($this->options['route_names'])) {
            $this->updateMetrics($metricsBag, $totalTime);
        }
    }

    protected function groupMetricName(string $groupName): string
    {
        return 'http_stats_' . $groupName;
    }
}