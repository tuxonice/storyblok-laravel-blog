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

    // Seconds a published read is served from the cache without asking Storyblok.
    'cache_fresh_for' => (int) env('STORYBLOK_CACHE_FRESH_FOR', 300),

    // Seconds beyond that a copy is kept to be served if Storyblok fails.
    'cache_keep_stale_for' => (int) env('STORYBLOK_CACHE_KEEP_STALE_FOR', 86400),

    // Origins allowed to frame draft pages: the Storyblok Visual Editor (EU).
    // Comma separated; add your region's editor origin if it differs.
    'editor_origins' => env('STORYBLOK_EDITOR_ORIGINS', 'https://app.storyblok.com'),

    // Secret set on the Storyblok webhook (Settings -> Webhooks). Storyblok signs
    // each request with it; the endpoint refuses everything while it is empty.
    'webhook_secret' => env('STORYBLOK_WEBHOOK_SECRET'),
];
