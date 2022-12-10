<?php

namespace Ensi\LaravelMetrics\Labels;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Ensi\LaravelPrometheus\LabelMiddlewares\LabelMiddleware;

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