<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Enums;

use Gupo\Enum\BaseEnum;
use Gupo\Enum\Supports\Message;
use Symfony\Component\HttpFoundation\Response;

/**
 * 响应码
 */
final class ResponseEnum extends BaseEnum
{
    #[Message('TOKEN失效')]
    public const UNAUTHORIZED = Response::HTTP_UNAUTHORIZED;

    #[Message('暂无权限')]
    public const FORBIDDEN = Response::HTTP_FORBIDDEN;
}
