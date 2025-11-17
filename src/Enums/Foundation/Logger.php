<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Enums\Foundation;

use BrightLiu\LowCode\Enums\Traits\LoggerProxy;

enum Logger: string
{
    use LoggerProxy;

    // 默认
    case LARAVEL = 'stack';

    // API服务
    case API_SERVICE = 'api-service';

    // authing中间件
    case AUTHING = 'auth';


    // 低代码列表
    case LOW_CODE_LIST = 'low-code-list';

    // 宽表查询
    case WIDTH_TABLE_DATA_RESIDENT = 'width-data-resident';

    // 管理居民
    case MANAGE_RESIDENT = 'manage-resident';

    // 管理任务
    case MANAGEMENT_TASK = 'management-task';

    // 通知
    case NOTIFICATION = 'notification';

    // Kafka
    case KAFKA = 'kafka';

    // Consumer
    case Consumer = 'consumer';

    //资源中心
    case BMO_SOURCE = 'bmo-source';

    //登录用户记录
    case LOGIN_USER = 'login-user';


    //开放接口错误日志
    case OPEN_API_ERROR = 'open-api-error';

    case BMP_BASE_LINE_ERROR = 'bmp-base-line-error';

    case BMP_CHEETAH_MEDICAL_CROWDKIT_ERROR = 'bmp-cheetah-medical-crowdkit-error';

    case REGION_PERMISSION_ERROR = 'region-permission-error';


    case DATA_PERMISSION_ERROR = 'data-permission-error';


    case BMP_CHEETAH_MEDICAL_ERROR = 'bmp-cheetah-medical-error';

    case BMP_CHEETAH_MEDICAL_DEBUG = 'bmp-cheetah-medical-debug';

    case BMO_AI = 'bmp-ai';


    case BMO_AUTH_DEBUG = 'bmp-auth-debug';


}
