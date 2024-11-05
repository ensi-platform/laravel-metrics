<?php

namespace Ensi\LaravelMetrics\Workers;

use Ensi\LaravelPrometheus\MetricsBag;
use Ensi\LaravelPrometheus\OnDemandMetrics\OnDemandMetric;
use Ensi\LaravelPrometheus\Prometheus;
use Swoole\Http\Server;

class WorkerUsage implements OnDemandMetric
{
    public function register(MetricsBag $metricsBag): void
    {
        if ($this->hasSwoole()) {
            $metricsBag->gauge('workers_total');
            $metricsBag->gauge('workers_idle');
        }
    }

    public function update(MetricsBag $metricsBag): void
    {
        if ($this->hasSwoole()) {
            app()->terminating(fn() => Prometheus::update('workers_total', $this->getTotal()));
            app()->terminating(fn() => Prometheus::update('workers_idle', $this->getIdle()));
        }
    }

    public function getTotal(): int
    {
        return app(Server::class)->stats()['worker_num'];
    }

    public function getIdle(): int
    {
        return app(Server::class)->stats()['idle_worker_num'];
    }

    private function hasSwoole(): bool
    {
        return app()->bound(Server::class);
    }
}
