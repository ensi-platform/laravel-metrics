<?php

namespace Madridianfox\LaravelMetrics\LabelProcessors;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Madridianfox\LaravelPrometheus\LabelProvider;

class HttpRequestLabelProvider implements LabelProvider
{
    public function labels(): array
    {
        return ['endpoint'];
    }

    public function values(): array
    {
        /** @var Request $request */
        $request = request();
        $path = Route::current()?->uri;
        return [
            $request->method() . ' ' . $path,
        ];
    }
}