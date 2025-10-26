<?php

return [
    //本应用的 appId & appSecret  向用户中心后端开发 提供 地区&环境&system_code，后方可获取
    "app_id"     => env('BMO_APP_ID'),
    "app_secret" => env('BMO_APP_SECRET'),

    'auth'                          => [
        //用户中心OrgID 预留
        'org_id' => env("BMO_ORG_ID", ''),

        //用户中心api地址
        'uri'    => env('BMO_AUTH_URI'),
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