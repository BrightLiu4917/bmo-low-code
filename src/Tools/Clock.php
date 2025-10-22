<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Tools;

use Illuminate\Support\Carbon;

final class Clock
{
    private static ?Carbon $datetime = null;

    public static function now(): Carbon
    {
        if (is_null(self::$datetime)) {
            self::flush();
        }

        return self::$datetime;
    }

    public static function flush(): void
    {
        self::$datetime = now();
    }
}
