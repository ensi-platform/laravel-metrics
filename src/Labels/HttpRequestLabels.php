<?php

namespace Ensi\LaravelMetrics\Labels;

use Ensi\LaravelPrometheus\LabelMiddlewares\LabelMiddleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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
