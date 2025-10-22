<?php
return [
    'auth'=>[
        'org_id' => env("BMO_ORG_ID",''),
    ],

    //宝庆老师业务中台uri
    'bmp_cheetah_medical_crowd_kit'=>[
        'uri' => env('BMP_CHEETAH_MEDICAL_CROWD_KIT_URI')
    ],

    //童java 的人群调用uri 基线人群等数据
    'bmp_baseline_crowd' => [
        'uri' => env('BMP_BASELINE_CROWD_URI'),
    ],
];