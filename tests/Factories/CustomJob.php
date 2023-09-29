<?php

namespace Ensi\LaravelMetrics\Tests\Factories;

use Illuminate\Contracts\Queue\Job as JobInterface;
use Illuminate\Queue\Jobs\Job as QueueJob;
use Faker\Factory;

class CustomJob extends QueueJob implements JobInterface
{
    public string $connection;

    public function __construct(public $connectionName, public $queue, public $attempts = 1)
    {
        $this->connection = $this->connectionName;
    }

    public function __invoke()
    {
    }

    public static function factory(): self
    {
        $faker = Factory::create();

        return new CustomJob(
            connectionName: $faker->word(),
            queue: $faker->word(),
            attempts: $faker->numberBetween(0, 10),
        );
    }

    public function getJobId(): string
    {
        return 'default';
    }

    public function getRawBody(): string
    {
        return '';
    }

    public function attempts(): int
    {
        return $this->attempts;
    }
}
