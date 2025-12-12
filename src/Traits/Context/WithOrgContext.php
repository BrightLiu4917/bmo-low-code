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

    protected ?string $contextOrgName = null;

    protected ?string $contextArcCode = null;

    protected ?string $contextArcName = null;

    protected ?string $contextArcType = null;

    protected ?array $contextManageAreaCodes = [];

    protected ?array $contextManageOrgCodes = [];


    protected ?string $contextAffiliatedOrgCode = null;

    protected ?string $contextAffiliatedOrgName = null;


    /**
     * 所属机构编码
     * @return string
     */

    public function getAffiliatedOrgCode(): string
    {
        if (empty($this->contextAffiliatedOrgCode)) {
            $this->contextAffiliatedOrgCode = OrgContext::instance()->getAffiliatedOrgCode();
        }

        return $this->contextAffiliatedOrgCode;
    }

    /**
     * 所属机构名称
     * @return string
     */
    public function getAffiliatedOrgName(): string
    {
        if (empty($this->contextAffiliatedOrgName)) {
            $this->contextAffiliatedOrgName = OrgContext::instance()->getAffiliatedOrgName();
        }

        return $this->contextAffiliatedOrgName;
    }


    public function getOrgCode(): string
    {
        if (empty($this->contextOrgCode)) {
            $this->contextOrgCode = OrgContext::instance()->getOrgCode();
        }

        return $this->contextOrgCode;
    }

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



    public function getOrgName(): string
    {
        if (empty($this->contextOrgName)) {
            $this->contextOrgName = OrgContext::instance()->getOrgName();
        }

        return $this->contextOrgName;
    }

    public function getManageOrgCode(): array
    {
        if (empty($this->contextManageOrgCodes)) {
            $this->contextManageOrgCodes = OrgContext::instance()->getManageOrgCode();
        }

        return $this->contextManageOrgCodes;
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
