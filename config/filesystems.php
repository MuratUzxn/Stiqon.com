<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    */

    'default' => env('FILESYSTEM_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | local   – private application files. Never served by the framework's
    |           built-in file route ('serve' => false).
    | uploads – customer print files and their previews. Private; delivered
    |           only through the authorised /dateien routes.
    | media   – images maintained in the admin (references, product photos).
    |           Lives directly below public/ so that no symlink is required
    |           on shared hosting.
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'serve' => false,
            'throw' => false,
            'report' => false,
        ],

        'uploads' => [
            'driver' => 'local',
            'root' => storage_path('app/private/uploads'),
            'serve' => false,
            'visibility' => 'private',
            'throw' => true,
            'report' => false,
        ],

        'media' => [
            'driver' => 'local',
            'root' => public_path('media'),
            'url' => rtrim(env('APP_URL', 'http://localhost'), '/').'/media',
            'visibility' => 'public',
            'throw' => false,
            'report' => false,
        ],

        // Alias for packages that expect a disk named "public".
        'public' => [
            'driver' => 'local',
            'root' => public_path('media'),
            'url' => rtrim(env('APP_URL', 'http://localhost'), '/').'/media',
            'visibility' => 'public',
            'throw' => false,
            'report' => false,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    |
    | Intentionally empty: the shop must run on hosts without symlink support.
    |
    */

    'links' => [],

];
