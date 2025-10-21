<?php


namespace BrightLiu\LowCode\Enums\Model\DatabaseSource;

use Gupo\Enum\BaseEnum;
use Gupo\Enum\Traits\ToRule;
use Gupo\Enum\Supports\Message;
use Gupo\Enum\Traits\AnnotationScan;
use Gupo\Enum\Traits\ToModelAttribute;

/**
 * 数据源:类型
 */
final class SourceTypeEnum extends BaseEnum
{
    use ToRule, ToModelAttribute;

    #[Message('数仓')]
    public const NO = 1;

    #[Message('业务')]
    public const YES = 2;
}
