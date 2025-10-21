<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Enums;

use Gupo\Enum\BaseEnum;
use Gupo\Enum\Supports\Message;

/**
 * 请求头
 */
final class HeaderEnum extends BaseEnum
{
    #[Message('AUTHORIZATION')]
    public const AUTHORIZATION = 'AUTHORIZATION';

    #[Message('病种CODE')]
    public const DISEASE_CODE = 'X-Gp-Disease-Code';

    #[Message('机构ID')]
    public const ORG_ID = 'X-Gp-Org-Id';

    #[Message('系统CODE')]
    public const SYSTEM_CODE = 'X-Gp-System-Code';

    #[Message('请求来源')]
    public const REQUEST_SOURCE = 'X-Gp-Request-Source';
}
