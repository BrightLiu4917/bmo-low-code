<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Enums\Model\Resident;

use Gupo\Enum\BaseEnum;
use Gupo\Enum\Supports\Message;
use Gupo\Enum\Traits\AnnotationScan;

/**
 * 个人档案数据来源
 */
final class DataSourceEnum extends BaseEnum
{
    use AnnotationScan;

    #[Message('默认')]
    public const DEFAULT = 0;

    #[Message('档案采集')]
    public const ARCHIVE_COLLECTION = 1;

    #[Message('任务打卡')]
    public const TASK_PUNCH = 2;

    #[Message('评估问卷')]
    public const ASSESSMENT_QUESTIONNAIRE = 3;

    #[Message('院内采集')]
    public const IN_HOSPITAL_COLLECTION = 9;
}
