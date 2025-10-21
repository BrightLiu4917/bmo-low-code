<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Enums\Model\AdminPreference;

use App\Support\Annotation\EnumDictionary;
use Gupo\Enum\BaseEnum;
use Gupo\Enum\Supports\Message;
use Gupo\Enum\Traits\ToModelAttribute;
use Gupo\Enum\Traits\ToRule;

/**
 * 管理员-偏好设置:场景
 */
final class SceneEnum extends BaseEnum
{
    use ToRule, ToModelAttribute;

    #[Message('自定义列表列')]
    public const LIST_COLUMNS = 'list_columns';
}
