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

    protected ?string $contextArcCode = null;

    protected ?string $contextArcName = null;

    protected ?string $contextArcType = null;

    protected ?array $contextManageAreaCodes = [];

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

    public function getArcCode(): string
    {
        if (empty($this->contextArcCode)) {
            $this->contextArcCode = OrgContext::instance()->getArcCode();
        }

        return $this->contextArcCode;
    }

    public function getManageAreaCodes(): array
    {
        if (empty($this->contextManageAreaCodes)) {
            $this->contextManageAreaCodes = OrgContext::instance()->getManageAreaCode();
        }
        return $this->contextManageAreaCodes;
    }

    public function getArcName(): string
    {
        if (empty($this->contextArcName)) {
            $this->contextArcName = OrgContext::instance()->getArcName();
        }

        return $this->contextArcName;
    }

    public function getArcType(): string
    {
        if (empty($this->contextArcType)) {
            $this->contextArcType = OrgContext::instance()->getArcType();
        }

        return $this->contextArcType;
    }
}
