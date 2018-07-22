<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Package driver
    |--------------------------------------------------------------------------
    |
    | The package supports different drivers for translation management.
    |
    | Supported: "file"
    |
    */

    'driver' => 'file',

    /*
    |--------------------------------------------------------------------------
    | Route group configuration
    |--------------------------------------------------------------------------
    |
    | The package ships with routes to handle language management. Update the
    | configuration here to configure the routes with your preferred goup options.
    |
    */

    'route_group_config' => [
        'middleware' => 'web',
        'namespace' => 'JoeDixon\\Translation\\Http\\Controllers'
    ],

    /*
    |--------------------------------------------------------------------------
    | Translation methods
    |--------------------------------------------------------------------------
    |
    | Update this array to tell the package which methods it should look for
    | when finding missing translations.
    |
    */

    'translation_methods' => ['trans', '__'],

    /*
    |--------------------------------------------------------------------------
    | Scan paths
    |--------------------------------------------------------------------------
    |
    | Update this array to tell the package which directories to scan when
    | looking for missing translations.
    |
    */

    'scan_paths' => [app_path(), resource_path()]
];
