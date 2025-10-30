<?php
return [
    'region' => [
        'table'          => env('DB_REGION_TABLE', 'mdm_admnstrt_rgn_y'),
        'driver'         => 'mysql',
        'host'           => env('DB_REGION_HOST', '127.0.0.1'),
        'port'           => env('DB_REGION_PORT', '3306'),
        'database'       => env('DB_REGION_DATABASE', 'forge'),
        'username'       => env('DB_REGION_USERNAME', 'forge'),
        'password'       => env('DB_REGION_PASSWORD', ''),
        'charset'        => 'utf8mb4',
        'collation'      => 'utf8mb4_unicode_ci',
        'prefix'         => '',
        'prefix_indexes' => true,
        'strict'         => true,
        'engine'         => null,
        'options'        => extension_loaded('pdo_mysql') ? array_filter([
            PDO::MYSQL_ATTR_SSL_CA     => env('MYSQL_ATTR_SSL_CA'),
            PDO::ATTR_TIMEOUT          => env(
                'DB_REGION_CONNECTION_TIMEOUT',
                10
            ),
            PDO::ATTR_EMULATE_PREPARES => env('DB_REGION_PREPARES', false),
        ]) : [],
    ],
];