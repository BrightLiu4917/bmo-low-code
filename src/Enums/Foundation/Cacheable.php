<?php

declare(strict_types = 1);

namespace BrightLiu\LowCode\Enums\Foundation;

use BrightLiu\LowCode\Enums\Traits\CacheProxy;

enum Cacheable: string
{
    use CacheProxy;

    /* --------------------------------------------------------------- */

    // 用户中心-token
    case USER_CENTER_TOKEN = 'uc:token';

    // 用户中心-org_code
    case USER_CENTER_ORG_CODE = 'uc:org_code';


    /* --------------------------------------------------------------- */

    /* --------------------------------------------------------------- */

    // API服务
    case API_SERVICE = 'as:common';

    /* --------------------------------------------------------------- */

    /* --------------------------------------------------------------- */

    // API服务
    case NORMAL_RECORD = 'n:record';

    /* --------------------------------------------------------------- */

    // 居民初始化
    case RESIDENTINIT = 'resident:init';

    /* --------------------------------------------------------------- */

    /* --------------------------------------------------------------- */

    // 资源:文件URL
    case RESOURCE_FILE_URL = 'r:file_url';

    /* --------------------------------------------------------------- */
}
