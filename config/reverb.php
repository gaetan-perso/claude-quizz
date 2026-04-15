<?php declare(strict_types=1);

return [

    'default' => env('REVERB_SERVER', 'reverb'),

    'servers' => [

        'reverb' => [
            'host'    => env('REVERB_HOST', '0.0.0.0'),
            'port'    => (int) env('REVERB_PORT', 8080),
            'scheme'  => env('REVERB_SCHEME', 'http'),
            'options' => [
                'tls' => [],
            ],
        ],

    ],

    'apps' => [

        'provider' => 'config',

        'apps' => [
            [
                'key'              => env('REVERB_APP_KEY', 'quiz-app-key'),
                'secret'           => env('REVERB_APP_SECRET', 'quiz-app-secret'),
                'app_id'           => env('REVERB_APP_ID', 'quiz-app'),
                'options'          => [
                    'host'    => env('REVERB_HOST', '0.0.0.0'),
                    'port'    => (int) env('REVERB_PORT', 8080),
                    'scheme'  => env('REVERB_SCHEME', 'http'),
                    'useTLS'  => env('REVERB_SCHEME', 'http') === 'https',
                ],
                'allowed_origins'  => ['*'],
                'ping_interval'    => env('REVERB_APP_PING_INTERVAL', 60),
                'ping_timeout'     => env('REVERB_APP_PING_TIMEOUT', 8),
                'activity_timeout' => env('REVERB_APP_ACTIVITY_TIMEOUT', 30),
                'max_message_size' => env('REVERB_APP_MAX_MESSAGE_SIZE', 10000),
            ],
        ],

    ],

    'scaling' => [
        'enabled'    => (bool) env('REVERB_SCALING_ENABLED', false),
        'channel'    => env('REVERB_SCALING_CHANNEL', 'reverb'),
        'server'     => [
            'url'      => env('REDIS_URL'),
            'host'     => env('REDIS_HOST', '127.0.0.1'),
            'port'     => (int) env('REDIS_PORT', 6379),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'database' => (int) env('REDIS_DB', 0),
        ],
    ],

    'pulse' => [
        'enabled' => (bool) env('REVERB_PULSE_ENABLED', false),
    ],

];
