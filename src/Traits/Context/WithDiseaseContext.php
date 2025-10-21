<?php

declare(strict_types = 1);

namespace BrightLiu\LowCode\Traits\Context;

use BrightLiu\LowCode\Context\DiseaseContext;

trait WithDiseaseContext
{
    /**
     * @var null|string
     */
    protected ?string $contextDiseaseCode = null;

    /**
     * @param string|object $diseaseCode
     *
     * @return static
     */
    public function byDisease(string|object $diseaseCode): static
    {
        $this->contextDiseaseCode = match (true) {
            is_object($diseaseCode) && method_exists($diseaseCode, 'getDiseaseCode') => $diseaseCode->getDiseaseCode(),
            default => $diseaseCode
        };

        return $this;
    }

    /**
     * @return string
     */
    public function getDiseaseCode(): string
    {
        if (empty($this->contextDiseaseCode)) {
            $this->contextDiseaseCode = DiseaseContext::instance()->getDiseaseCode();
        }
        return $this->contextDiseaseCode;
    }
}
