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
    'http_client_stats_settings' => [
        // 'external-domain.com' => [
        //     'labels' => ['host', 'path'],
        // ],
    ],
    'http_client_endpoint_avg_time' => [
        'enabled' => false,
        'domains' => ['*'], // '*' for all, or list like ['domain1.com', 'domain2.com']
    ],
    'http_client_percentiles' => [
        'enabled' => false,
        'domains' => ['*'], // '*' for all, or list like ['domain1.com', 'domain2.com']
        'endpoints' => [], // list of specific endpoints like ['domain1.com/path1', 'domain2.com/path2'], if empty - all
        'quantiles' => [0.5, 0.95, 0.99], // quantiles to collect
        'type' => 'summary', // 'summary' or 'histogram'
        'buckets' => [0.005, 0.01, 0.025, 0.05, 0.1, 0.25, 0.5, 1, 2.5, 5, 10], // for histogram
    ],
    'watch_queues' => [
        'default',
    ],
];
