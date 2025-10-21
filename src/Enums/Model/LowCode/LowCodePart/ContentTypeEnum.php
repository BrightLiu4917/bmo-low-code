<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Enums\Model\LowCode\LowCodePart;

use Gupo\Enum\BaseEnum;
use Gupo\Enum\Traits\ToRule;
use Gupo\Enum\Supports\Message;
use Gupo\Enum\Traits\ToModelAttribute;

/**
 * 低代码:零件类型
 */
final class ContentTypeEnum extends BaseEnum
{
    use ToRule, ToModelAttribute;

    #[Message('表头')]
    public const PART_TYPE_COLUMN = 1;

    #[Message('筛选')]
    public const PART_TYPE_SEARCH = 2;

    #[Message('操作栏按钮')]
    public const PART_TYPE_BUTTON = 3;

    #[Message('顶部按钮')]
    public const PART_TYPE_TOP_BUTTON = 4;

    #[Message('查询字段集合')]
    public const PART_TYPE_FIELD = 5;
}
