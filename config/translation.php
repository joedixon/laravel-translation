<?php

return [
    'driver' => 'file',

    'route_group_config' => [
        'middleware' => 'web',
        'namespace' => 'JoeDixon\\Translation\\Http\\Controllers'
    ],
    'scan_paths' => [app_path(), resource_path()]
];
