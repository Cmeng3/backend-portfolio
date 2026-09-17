<?php

return [
    'public_cache_seconds' => env('PUBLIC_CONTENT_CACHE_SECONDS', 60),
    'media_disk' => env('MEDIA_DISK', 'public'),
    'signed_media_urls' => env('MEDIA_SIGNED_URLS', false),
];
