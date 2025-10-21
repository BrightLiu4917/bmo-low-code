<?php

namespace BrightLiu\LowCode\Enums\Model\LowCode\LowCodeList;

use Gupo\Enum\BaseEnum;
use Gupo\Enum\Traits\ToRule;
use Gupo\Enum\Supports\Message;
use Gupo\Enum\Traits\ToModelAttribute;

/**
 * 低代码:列表类型
 */
final class ListTypeEnum extends BaseEnum
{
    use ToRule, ToModelAttribute;
    #[Message('默认')]
    public const  DEFAULT = 0;

    #[Message('通用')]
    public const GENERAL = 9;
}
