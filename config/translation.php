<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Package driver
    |--------------------------------------------------------------------------
    |
    | The package supports different drivers for translation management.
    |
    | Supported: "file", "database"
    |
    */
    'driver' => 'file',

    /*
    |--------------------------------------------------------------------------
    | Copy key to value - on default "app.locale" - single translations only
    |--------------------------------------------------------------------------
    | 
    | Most of the time the key of a "single" translation is also the value of the default app.locale. 
    | If this is true for you, just set this setting to true. Than while scanning for missing 
    | translations of your default "app.locale" the key will be copied to the value too.

    |      Scanning __('I love translation.')
    |      will result
    |      {
    |          'I love translation.':  "I love translation."
    |      }
    |
    */
    'copy_key_to_value_if_default_locale' => true,

    /*
    |--------------------------------------------------------------------------
    | Yandex auto translation
    |--------------------------------------------------------------------------
    | 
    | While scanning for missing translations 
    | yandex can to do the initial work.
    |
    |       Scanning __('I love automatic translations.')
    |       will result
    |       {
    |           I love automatic translations.':  "*yandexsays Ich liebe automatische Übersetzungen."
    |       }
    */
    'yandex' => [
        'api_key' => '',
        'exclude_locales' => [config('app.locale')],
        'prefix' => '*yandexsays '
    ],

    /*
    |--------------------------------------------------------------------------
    | Route group configuration
    |--------------------------------------------------------------------------
    |
    | The package ships with routes to handle language management. Update the
    | configuration here to configure the routes with your preferred group options.
    |
    */
    'route_group_config' => [
        'middleware' => 'web',
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
    'scan_paths' => [app_path(), resource_path()],

    /*
    |--------------------------------------------------------------------------
    | UI URL
    |--------------------------------------------------------------------------
    |
    | Define the URL used to access the language management too.
    |
    */
    'ui_url' => 'languages',

    /*
    |--------------------------------------------------------------------------
    | Database settings
    |--------------------------------------------------------------------------
    |
    | Define the settings for the database driver here.
    |
    */
    'database' => [

        'connection' => '',

        'languages_table' => 'languages',

        'translations_table' => 'translations',
    ],
];
