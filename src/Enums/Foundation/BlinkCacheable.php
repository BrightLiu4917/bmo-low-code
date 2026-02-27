<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Enums\Foundation;

use BrightLiu\LowCode\Enums\Traits\BlinkCacheProxy;

enum BlinkCacheable: string
{
    use BlinkCacheProxy;

    // 模型:数据源
    case MODEL_DATABASESOURCE = 'model:DatabaseSource';

    // BMP:人群分类
    case BMP_CROWD_GROUP = 'bmp:crowd_group';
}
