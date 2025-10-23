<?php
return [
    'auth'=>[
        //用户中心OrgID
        'org_id' => env("BMO_ORG_ID",''),
        'uri' => env('BMO_AUTH_URI'),
    ],

    //童java 基线人群服务
    'bmp_cheetah_medical_crowd_kit'=>[
        'uri' => env('BMP_CHEETAH_MEDICAL_CROWD_KIT_URI')
    ],

    //保庆老师的业务中台服务
    'bmp_cheetah_medical_platform' => [
        'uri' => env('BMP_CHEETAH_MEDICAL_PLATFORM_URI'),
    ],
];