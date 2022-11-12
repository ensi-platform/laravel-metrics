<?php

namespace Madridianfox\LaravelMetrics;

use Illuminate\Support\ServiceProvider;
use Madridianfox\LaravelMetrics\LabelProcessors\HttpRequestLabels;
use Madridianfox\LaravelPrometheus\Prometheus;

class MetricsServiceProvider extends ServiceProvider
{
    public function boot()
    {
        /** @var Prometheus $prometheus */
        $prometheus = resolve(Prometheus::class);

        $prometheus->addLabelProcessor(new HttpRequestLabels());
        $prometheus->declareCounter('http_request', ['code']);
    }
}