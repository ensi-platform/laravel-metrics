<?php

namespace Madridianfox\LaravelMetrics\Labels;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Madridianfox\LaravelPrometheus\LabelMiddlewares\LabelMiddleware;

class HttpRequestLabels implements LabelMiddleware
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