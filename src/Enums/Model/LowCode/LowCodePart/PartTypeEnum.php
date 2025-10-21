<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Enums\Model\LowCode\LowCodePart;

use Gupo\Enum\BaseEnum;
use Gupo\Enum\Traits\ToRule;
use Gupo\Enum\Supports\Message;
use Gupo\Enum\Traits\ToModelAttribute;

/**
 * 低代码:零件
 */
final class PartTypeEnum extends BaseEnum
{
    use ToRule, ToModelAttribute;

    #[Message('系统组件')]
    public const SYSTEM = 1;

    #[Message('客户自定义')]
    public const CUSTOMER = 2;

}
