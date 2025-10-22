<?php

declare(strict_types = 1);

namespace BrightLiu\LowCode\Traits\Context;

trait WithContext
{
    use  WithDiseaseContext, WithOrgContext, WithAdminContext;

    /**
     * @param object $contextService
     *
     * @return static
     */
    public function byContext(object $contextService): static
    {
        return $this->byDisease($contextService)->byOrgCode($contextService);
    }
}
