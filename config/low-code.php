<?php

return [
    "cache-model" => [
        // 是否开启 id/code 缓存
        'enable-id-code-cache' => env('ENABLE_ID_CODE_CACHE',false),

        // 是否开启 SQL 查询缓存（含 with）
        'enable-query-cache' => env('ENABLE_QUERY_CACHE',false),

        // 模型保存/删除时是否清理该模型 tag 下所有缓存
        'flush-tag-on-update' => env('CACHE_MODEL_FLUSH_TAG_ON_UPDATE',false),

        // 缓存有效时间（秒）
        'ttl' => env('CACHE_MODEL_TTL',600),
    ],

    //业务中台相关
    'bmo-baseline' => [
            // 基线数据库
            'database'=>[
                // 人群类型表 一般都是 feature_user_detail
                'crowd-type-table'=> env('DB_BUSINESS_CENTER_CROWD_TYPE_TABLE','feature_user_detail'),

                //人员宽表 主表
                'crowd-psn-wdth-table'=>env('DB_MEDICAL_CROWD_PSN_WDTH_TABLE','crowd_psn_wdth'),

                'default' => [
                    'driver' => 'mysql',
                    'host' => env('DB_MEDICAL_PLATFORM_HOST', '127.0.0.1'),
                    'port' => env('DB_MEDICAL_PLATFORM_PORT', '3306'),
                    'database' => env('DB_MEDICAL_PLATFORM_DATABASE', 'forge'),
                    'username' => env('DB_MEDICAL_PLATFORM_USERNAME', 'forge'),
                    'password' => env('DB_MEDICAL_PLATFORM_PASSWORD', ''),
                    'charset' => 'utf8mb4',
                    'collation' => 'utf8mb4_unicode_ci',
                    'prefix' => '',
                    'prefix_indexes' => true,
                    'strict' => true,
                    'engine' => null,
                    'options' => extension_loaded('pdo_mysql') ? array_filter([
                        PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
                        PDO::ATTR_TIMEOUT => env('DB_MEDICAL_PLATFORM_CONNECTION_TIMEOUT', 10),
                        PDO::ATTR_EMULATE_PREPARES => env('DB_MEDICAL_PLATFORM_PREPARES', false),
                    ]) : [],
                ],
            ]
    ],

    /**
     * Http模块
     */
    'http' => [
        'modules' => [
            'api' => [
                'prefix' => 'api',
                'middleware' => ['api', 'auth.disease'],
            ],
            'low-code' => [
                'prefix' => 'api',
                'middleware' => ['api', 'auth.disease'],
            ],
            'innerapi' => [
                'prefix' => 'innerapi',
//                'middleware' => ['auth.disease'],
            ]


        ],
    ],

    'dependencies' => []
];