<?php

return [
    'ignore_routes' => [
        'prometheus.*'
    ],
    'http_requests_stats_groups' => [
        'default' => [
            'type' => 'summary',
            'route_names' => ['*'],
            'time_window' => 60,
            'quantiles' => [0.5, 0.95],
        ],
    ],
    'watch_queues' => [
        'default',
    ]
];