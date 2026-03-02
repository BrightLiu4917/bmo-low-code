<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Models;

use Gupo\BetterLaravel\Database\BaseModel;

/**
 * 接口访问日志
 */
final class ApiAccessLog extends BaseModel
{
    /**
     * 指示模型是否主动维护时间戳。
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * @var array<string,string>
     */
    public $casts = [
        'request_params' => 'json',
        'response_data' => 'json',
    ];
}
