<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Models\Resident;

use Gupo\BetterLaravel\Database\BaseModel;

/**
 * 关注居民
 *
 * @mixin IdeHelperFollowResident
 */
class FollowResident extends BaseModel
{
    /**
     * 标记为禁止维护updated_at
     */
    public const UPDATED_AT = null;
}
