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
        // 'default' => [
        //     'type' => 'histogram',
        //     'route_names' => ['*'],
        //     'buckets' => [0.01, 0.02, 0.04, 0.08, 0.16],
        // ],
    ]
];