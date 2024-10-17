<?php

return [
    'ignore_commands' => [
        'kafka:consume',
        'queue:work',
        'optimize',
        'storage:link',
    ],
    'ignore_routes' => [
        'prometheus.*',
    ],
    'http_requests_stats_groups' => [
        // set your setting, like in README.md
//        'default' => [
//            'type' => 'histogram',
//            'route_names' => ['*'],
//            'buckets' => [0.005, 0.01, 0.025, 0.05, 0.1, 0.25, 0.5, 1, 2.5, 5, 10],
//        ],
    ],
    'watch_queues' => [
        'default',
    ],
];
