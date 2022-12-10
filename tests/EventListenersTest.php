<?php

namespace Ensi\LaravelMetrics\Tests;

use Illuminate\Database\Connection;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Ensi\LaravelMetrics\LatencyProfiler;
use Ensi\LaravelPrometheus\Prometheus;
use Ensi\LaravelPrometheus\PrometheusManager;
use Mockery\MockInterface;

class EventListenersTest extends TestCase
{
    public function testListenDatabaseQueries(): void
    {
        /** @var LatencyProfiler|MockInterface $latenctyProfiler */
        $latencyProfiler = $this->mock(LatencyProfiler::class);
        $latencyProfiler->expects('addTimeQuant');

        /** @var Connection $connection */
        $connection = DB::connection();

        Event::dispatch(new QueryExecuted("example", [], 1, $connection));
    }

    public function testListenMessageLogged(): void
    {
        Prometheus::shouldReceive('update')
            ->once()
            ->withArgs(['log_messages_count', 1, ['info']]);
        logger()->info("hello");
    }
}