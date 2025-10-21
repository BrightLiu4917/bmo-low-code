<?php

declare(strict_types = 1);

namespace BrightLiu\LowCode\Traits\Context;

use BrightLiu\LowCode\Context\OrgContext;

trait WithOrgContext
{
    /**
     * @var null|string
     */
    protected ?string $contextOrgCode = null;

    /**
     * @param string|object $orgCode
     *
     * @return static
     */
    public function byOrgCode(string|object $orgCode): static
    {
        $this->contextOrgCode = match (true) {
            is_object($orgCode) && method_exists($orgCode, 'getOrgCode') => $orgCode->getOrgCode(),
            default => $orgCode
        };

        return $this;
    }

    /**
     * @return string
     */
    public function getOrgCode(): string
    {
        if (empty($this->contextOrgCode)) {
            $this->contextOrgCode = OrgContext::instance()->getOrgCode();
        }

        return $this->contextOrgCode;
    }
}
