<?php

return [
    'space_id' => env('STORYBLOK_SPACE_ID'),

    // Management API: generator only, never deployed to production.
    'management_token' => env('STORYBLOK_MANAGEMENT_TOKEN'),

    // Content Delivery API.
    'delivery_token' => env('STORYBLOK_DELIVERY_TOKEN'),
    'preview_token' => env('STORYBLOK_PREVIEW_TOKEN'),

    // published, or draft for a preview environment
    'version' => env('STORYBLOK_DEFAULT_VERSION', 'published'),

    // Leave unset for an EU space (see the package's Configuration -> Regions).
    'content_base_uri' => env('STORYBLOK_CONTENT_BASE_URI'),
    'management_base_uri' => env('STORYBLOK_MANAGEMENT_BASE_URI', 'https://mapi.storyblok.com/v1/'),

    // The generator deletes every *Transfer.php in output_path on each run:
    // keep hand-written classes out of it.
    'namespace' => 'App\\Storyblok\\Transfers',
    'output_path' => app_path('Storyblok/Transfers'),
    'definitions_path' => resource_path('storyblok'),

    'cache_store' => 'storyblok',
];
