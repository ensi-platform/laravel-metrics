<?php

namespace Ensi\LaravelMetrics\Kafka;

enum KafkaResponseStatus: int
{
    case SUCCESS = 0;
    case FAILURE = 1;
}
