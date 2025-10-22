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
    'bmo-business-center' => [
            'crowd-type-table'=> env('BMO_BUSINESS_CENTER_CROWD_TYPE_TABLE','feature_user_detail')
    ]
];