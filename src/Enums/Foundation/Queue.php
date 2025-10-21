<?php

declare(strict_types = 1);

namespace BrightLiu\LowCode\Enums\Foundation;

enum Queue: string
{
    // 管理方案相关
    case MANAGEMENT = 'management';

    // 任务相关
    case TASK = 'task';

    // 宽表相关
    case RESIDENTWIDTHDATA = 'resident-width-data';

    // 通知相关
    case NOTIFICATION = 'notification';

    //企业微信工作卡片通知
    case CORPWECHATWORKCARDNOTIFICATION = 'corp-wechat-work-card-notification';
}
