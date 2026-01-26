<?php

use BrightLiu\LowCode\Enums\Foundation\Middleware;
use BrightLiu\LowCode\Resources\LowCode\LowCodeList\QuerySource;
use BrightLiu\LowCode\Services\LowCode\QueryBuilder\DefaultQueryBuilder;

return [
    // 开发环境是否
    'dev-enable' => env('DEV_ENABLE', false),

    // 自定义查询构建器
    'custom-query' => [
        'enabled' => env('BLC_CUSTOM_QUERY_ENABLED', false),

        'builder' => env('BLC_CUSTOM_QUERY_BUILDER', DefaultQueryBuilder::class),
    ],

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
                'middleware' => ['api', Middleware::AUTH_DISEASE],
            ],
            'low-code' => [
                'prefix' => 'api',
                'middleware' => ['api', Middleware::AUTH_DISEASE],
            ],
            'innerapi' => [
                'prefix' => 'innerapi',
//                'middleware' => [Middleware::AUTH_DISEASE],
            ]


        ],
    ],

    'dependencies' => [
        //患者列表的出参
         QuerySource::class=>'你的resource文件路径'
    ],

    //使用什么字段作为查询条件 默认 disease_code
    'low-code-set-use-table-field' => env('LOW_CODE_SET_USE_TABLE_FIELD','disease_code'),

    //区域权限条件符号默认or
    'region-permission-symbolic-condition'=>env('REGION_PERMISSION_SYMBOLIC_CONDITION','or'),

    //使用奢么数据权限 默认区域
    'use-data-permission'=>env('USE_DATA_PERMISSION','region'),

    //使用什么字段作为区域权限条件 默认 region_code
    'use-region-code'=> env('USE_REGION_CODE','4602'),

    //设置区域缓存时间
    'region-cache-ttl'=>env('REGION_CACHE_TTL',60),

    // 数据权限预设(对应 data_permission表)
    'preset-permission-data' => [
        'enabled' => env('BLC_PRESET_PERMISSION_DATA_ENABLED', false),

        'items' => [
            [
                'title' => '纳管机构',
                'code' => 'org',
                'symbol' => 'in',
                'permission_key' => 'manage_org_code',
            ],
            [
                'title' => '地区编码',
                'code' => 'region',
                'symbol' => 'multiple_field',
                'permission_key' => 'multiple_field',
            ],
            [
                'title' => '纳管机构与转诊机构',
                'code' => 'org_and_referral',
                'symbol' => 'multiple_field',
                'permission_key' => 'manage_org_code',
            ],
            [
                'title' => '地区编码与转诊机构',
                'code' => 'region_and_referral',
                'symbol' => 'multiple_field',
                'permission_key' => 'multiple_field',
            ],
        ]
    ]
];