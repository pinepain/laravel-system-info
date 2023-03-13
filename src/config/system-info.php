<?php declare(strict_types=1);

return [
    'http' => [
        // Root path for all HTTP endpoints, be careful if you cache routes since if you would set
        // different config value after caching routes, it won't have any effect.
        'root-path' => env('SYSTEM_INFO__HTTP_PATH', 'system-info'),
        // When false, would allow to execute complete status checks for non-authorized requests
        // By default fail-fast approach is used and full check allows to continue checking
        'full-check-is-private' => env('SYSTEM_INFO__FULL_CHECK_IS_PRIVATE', true),
        // When false, would allow to execute granular status checks for non-authorized requests
        'custom-checks-are-private' => env('SYSTEM_INFO__CUSTOM_CHECKS_ARE_PRIVATE', true),
        // When false, would allow to view status check details for non-authorized requests
        'details-are-private' => env('SYSTEM_INFO__DETAILS_ARE_PRIVATE', true),
        // When false, would include version headers for non-authorized requests
        'version-is-private' => env('SYSTEM_INFO__VERSION_IS_PRIVATE', true),

        // A list of restricted IPs where system-info/* endpoints are available
        'allowed-ips' => env('SYSTEM_INFO__ALLOWED_IPS', 'private'),
        // A list of restricted token to be used for authorizing when accessing from public locations
        'allowed-tokens' => env('SYSTEM_INFO__ACCESS_TOKENS', ''),

        // A list of system-info.* family routes that available from non-restricted IPs and available GET params.
        // When params is true, all GET params are passed through. When it's array, key denotes allowed GET param names
        // and value hardcode the value or allows any value to be passed when '*'.
        'public-routes' => [
            'system-info.version' => env('SYSTEM_INFO__VERSION_IS_PUBLIC', false),
            'system-info.status' => env('SYSTEM_INFO__STATUS_IS_PUBLIC', false),
            'system-info.ping' => env('SYSTEM_INFO__PING_IS_PUBLIC', false),
            'system-info.time' => env('SYSTEM_INFO__TIME_IS_PUBLIC', false),
            'system-info.request' => env('SYSTEM_INFO__REQUEST_IS_PUBLIC', false),
            'system-info.echo' => env('SYSTEM_INFO__ECHO_IS_PUBLIC', false),
            'system-info.server' => env('SYSTEM_INFO__SERVER_IS_PUBLIC', false),
        ],
    ],
];
