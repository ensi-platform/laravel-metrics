<?php

namespace Madridianfox\LaravelMetrics\LabelProcessors;

use Madridianfox\LaravelPrometheus\LabelProcessor;

class HttpRequestLabels implements LabelProcessor
{
    public function labels(): array
    {
        return ['path'];
    }

    public function values(): array
    {
        return [
            request()->path(),
        ];
    }
}