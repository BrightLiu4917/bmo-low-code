<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Enums\Foundation;

use BrightLiu\LowCode\Middleware\DiseaseAuthenticate;
use BrightLiu\LowCode\Middleware\DiseaseAuthenticateInner;

class Middleware
{
    /**
     * 病种操作认证
     */
    public const AUTH_DISEASE = DiseaseAuthenticate::class;

    /**
     * 病种操作认证:Inner
     */
    public const AUTH_DISEASE_INNER = DiseaseAuthenticateInner::class;
}
