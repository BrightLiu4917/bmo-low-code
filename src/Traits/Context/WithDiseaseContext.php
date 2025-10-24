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
     * @var null|string
     */
    protected ?string $contextSceneCode = null;

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

    public function byScene(string|object $sceneCode): static
    {
        $this->contextSceneCode = match (true) {
            is_object($sceneCode) && method_exists($sceneCode, 'getSceneCode') => $sceneCode->getSceneCode(),
            default => $sceneCode
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

    public function getSceneCode(): string
    {
        if (empty($this->contextSceneCode)) {
            $this->contextSceneCode = DiseaseContext::instance()->getSceneCode();
        }

        return $this->contextSceneCode;
    }
}
