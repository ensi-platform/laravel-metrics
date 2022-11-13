<?php

namespace Madridianfox\LaravelMetrics\LabelProcessors;

use Illuminate\Http\Request;
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

        $rawPath = $request->path();
        // todo доработать замену параметров на плейсхолдеры
        $path = preg_replace('#/\d+#', '/ID', $rawPath);
        return [
            $request->method() . ' ' . $path,
        ];
    }
}