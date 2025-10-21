<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Tools;

use Illuminate\Support\Str;

final class Uuid
{
    public static function generate(): string
    {
        return Str::orderedUuid()->getHex()->toString();
    }
}
