<?php

return [
    //本应用的 appId & appSecret  向用户中心后端开发 提供 地区&环境&system_code，后方可获取
    "app_id"     => env('BMO_APP_ID'),
    "app_secret" => env('BMO_APP_SECRET'),

    //用户中心
    'auth'                          => [
        //用户中心OrgID 预留
        'org_id' => env("BMO_ORG_ID", ''),

        //用户中心api地址
        'uri'    => env('BMO_AUTH_URI'),
    ],

    //ai 服务
    'ai'                          => [
        'app_id'       => env("BMO_AI_APP_ID", 'app_syonb2uabktxqfir'),
        "app_key"      => env("BMO_AI_APP_KEY", 'key_bpwhmxbwyzk4vgzw'),
        "bot_id"       => env("BMO_AI_BOT_ID", '1004'),
        'app_secret'   => env(
            "BMO_AI_APP_SECRET",
            '1ba8df194ef4bc8d709f554c87eb4e32e2e09fa3295fa310bdcd404bc3d553ef'
        ),
        'uri'          => env(
            'BMO_AI_URI',
            'http://apisix-gateway.apisix/ai-plat-api/'
        ),
        'enable'       => env('BMO_AI_ENABLE', false),
        'cache_ttl'    => env('BMO_AI_CACHE_TTL', 30),
        'cache_enable' => env('BMO_AI_CACHE_ENABLE', false),
    ],

    //童java 基线人群服务
    'bmp_cheetah_medical_crowd_kit' => [
        'uri' => env('BMP_CHEETAH_MEDICAL_CROWD_KIT_URI'),
    ],

    //宝庆老师的业务中台服务
    'bmp_cheetah_medical_platform'  => [
        'uri' => env('BMP_CHEETAH_MEDICAL_PLATFORM_URI'),
    ],
];