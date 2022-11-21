<?php

namespace Madridianfox\LaravelMetrics\LabelMiddlewares;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Madridianfox\LaravelPrometheus\LabelMiddlewares\LabelMiddleware;

class HttpRequestLabelMiddleware implements LabelMiddleware
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