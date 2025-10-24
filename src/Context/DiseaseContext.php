<?php

declare(strict_types = 1);

namespace BrightLiu\LowCode\Context;

use BrightLiu\LowCode\Models\LowCodeDisease;


/**
 * 病种上下文
 */
final class DiseaseContext
{
    /**
     * @var string
     */
    protected string $diseaseCode = '';

    /**
     * @var string
     */
    protected string $sceneCode = '';

    /**
     * @var null|LowCodeDisease
     */
    protected ?LowCodeDisease $disease = null;

    /**
     * @return static
     */
    public static function instance(): static
    {
        return app('context:disease');
    }

    /**
     * @param string $diseaseCode
     * @param string $sceneCode
     *
     * @return static
     */
    public static function init(string $diseaseCode, string $sceneCode = ''): static
    {
        return tap(
            static::instance(),
            function (DiseaseContext $context) use ($diseaseCode, $sceneCode) {
                $context->setDiseaseCode($diseaseCode);

                $context->setSceneCode($sceneCode);
            }
        );
    }

    /**
     * @return string
     */
    public function getDiseaseCode(): string
    {
        return $this->diseaseCode;
    }

    /**
     * @return string
     */
    public function getLowerDiseaseCode(): string
    {
        return mb_strtolower($this->diseaseCode);
    }

    /**
     * @return null|LowCodeDisease
     */
    public function getDisease(): ?LowCodeDisease
    {
        if (empty($this->diseaseCode)) {
            return null;
        }

        return $this->disease = match (true) {
            empty($this->disease) => LowCodeDisease::query()
                ->where('code', $this->diseaseCode)
                ->first(['id', 'code', 'name', 'weight']),
            default => $this->disease
        };
    }

    public function getSceneCode(): string
    {
        return $this->sceneCode;
    }

    /**
     * @param string $value
     *
     * @return void
     */
    public function setDiseaseCode(string $value): void
    {
        if ($value === $this->diseaseCode) {
            return;
        }

        $this->diseaseCode = $value;

        $this->disease = null;
    }

    /**
     * @param null|LowCodeDisease $value
     *
     * @return void
     */
    public function setDisease(?LowCodeDisease $value): void
    {
        $this->disease = $value;

        $this->diseaseCode = $value?->code ?? '';
    }

    public function setSceneCode(string $value): void
    {
        $this->sceneCode = $value;
    }

    /**
     * @param string $diseaseCode
     * @param string $sceneCode
     * @param callable $callback
     *
     * @return mixed
     */
    public static function with(string $diseaseCode, string $sceneCode, callable $callback)
    {
        $context = static::instance();

        $latestDiseaseCode = $context->getDiseaseCode();

        $latestSceneCode = $context->getSceneCode();

        $context->setDiseaseCode($diseaseCode);

        $context->setSceneCode($sceneCode);

        try {
            return $callback();
        } finally {
            $context->setDiseaseCode($latestDiseaseCode);

            $context->setSceneCode($latestSceneCode);
        }
    }
}
