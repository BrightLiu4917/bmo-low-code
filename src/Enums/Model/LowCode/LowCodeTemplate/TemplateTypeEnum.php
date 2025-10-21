<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Enums\Model\LowCode\LowCodeTemplate;

use Gupo\Enum\BaseEnum;
use Gupo\Enum\Traits\ToRule;
use Gupo\Enum\Supports\Message;
use Gupo\Enum\Traits\ToModelAttribute;

/**
 * 低代码:模板类型
 */
final class TemplateTypeEnum extends BaseEnum
{
    use ToRule, ToModelAttribute;

    #[Message('已纳管')]
    public const MANAGED = 1;

    #[Message('待纳管')]
    public const WAIT_MANAGE = 2;

    #[Message('推荐纳管')]
    public const RECOMMEND_MANAGE = 3;

    #[Message('出组')]
    public const REMOVE_MANAGE = 4;

}
