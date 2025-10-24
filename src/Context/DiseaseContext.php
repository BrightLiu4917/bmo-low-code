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
     *
     * @return static
     */
    public static function init(string $diseaseCode): static
    {
        return tap(
            static::instance(),
            function (DiseaseContext $context) use ($diseaseCode) {
                $context->setDiseaseCode($diseaseCode);
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

    /**
     * @param string $diseaseCode
     * @param callable $callback
     *
     * @return mixed
     */
    public static function with(string $diseaseCode, callable $callback)
    {
        $context = static::instance();

        $latestDiseaseCode = $context->getDiseaseCode();

        $context->setDiseaseCode($diseaseCode);

        try {
            return $callback();
        } finally {
            $context->setDiseaseCode($latestDiseaseCode);
        }
    }
}
