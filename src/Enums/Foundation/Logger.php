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

    // 中台同步
    case BMO_SYNCING = 'bmo-syncing';

    //人群分类
    case BUSINESS_CROWD_TYPE = 'business-crowd-type';

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

    //执行企业微信医生居民关系
    case CORP_WECHAT_EXECUTE_DOCTOR_RESIDENT_RELATIONSHIP = 'corp-wechat-execute-doctor-resident-relationship';


    //微信工作通知结果
    case CORP_WECHAT_WORK_NOTICE_RESPONSE = 'corp-wechat-work-notification-response';


    //企业微信工作通知调整人群
    case CORP_WECHAT_WORK_NOTIFICATION_UPDATE_CROWD_TYPE = 'corp-wechat-work-notification-update-crowd-type';

    //企业微信消息记录
    case CORP_WECHAT_CREATE_MESSAGE_RECORD = 'corp-wechat-create-message-record';


    //企业微信alert
    case CORP_WECHAT_ALERT_NOTICE = 'corp_wechat_alert_notice';


    //短链接
    case SHORT_LINK = 'short-link';

    //登录用户记录
    case LOGIN_USER = 'login-user';

    //规则引擎推送触发微信通知
    case TRIGGER_RULE_ENGINE_CORP_WECHAT_ALERT = 'trigger-rule-engine-corp-wechat-alert';

    //开放接口错误日志
    case OPEN_API_ERROR = 'open-api-error';

    case BUSINESS_MIDDLE_PLATFORM_ERROR = 'business_middle_platform_error';

}
