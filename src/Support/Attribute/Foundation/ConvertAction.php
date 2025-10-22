<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Support\Attribute\Foundation;

class ConvertAction
{
    public const ACTION_VALUE = 'value';

    public const ACTION_VARIANT = 'variant';

    public const ACTION_READONLY = 'readonly';

    public const ACTION_UNIT = 'unit';

    public const ACTION_METADATA = 'metadata';

    public const ACTION_INFORMATION = 'information';

    public static function preset(): array
    {
        return [
            self::ACTION_VALUE,
            self::ACTION_VARIANT,
            self::ACTION_READONLY,
            self::ACTION_UNIT,
            self::ACTION_METADATA,
            self::ACTION_INFORMATION,
        ];
    }
}
