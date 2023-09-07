<?php

namespace Ensi\LaravelMetrics;

class Helper
{
    public static function duration(): float
    {
        return defined('LARAVEL_START') ? (microtime(true) - LARAVEL_START) : 0;
    }
}
