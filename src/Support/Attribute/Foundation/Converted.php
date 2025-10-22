<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Support\Attribute\Foundation;

class Converted
{
    public function __construct(
        protected string $key,
        protected mixed $value = null,
        protected ?string $variant = null,
        protected ?string $unit = null,
        protected ?string $information = null,
        protected ?bool $readonly = null,
        protected ?array $metadata = null
    ) {
    }

    public function toArray(bool $realityKey = false): array
    {
        return [
            ...($realityKey ? [$this->key => $this->value] : ['value' => $this->value]),
            'variant' => $this->variant,
            'unit' => $this->unit,
            'information' => $this->information,
            'readonly' => $this->readonly,
            'metadata' => $this->metadata,
        ];
    }

    public function toPrefixing(array $only = [], bool $realityKey = false, string $separator = '.'): array
    {
        $data = $this->toArray($realityKey);

        // 仅保留指定字段
        if (!empty($only)) {
            $data = array_filter($data, fn ($key) => in_array($key, $only, true), ARRAY_FILTER_USE_KEY);
        }

        $prefixedData = [];
        foreach ($data as $key => $value) {
            $prefixedData["{$this->key}{$separator}{$key}"] = $value;
        }

        if ($realityKey) {
            $prefixedData[$this->key] = $this->value;
        }

        return $prefixedData;
    }

    public function getValue(mixed $default = null): mixed
    {
        return $this->value ?? $default;
    }

    public function getVariant(mixed $default = null): mixed
    {
        return $this->variant ?? $default;
    }

    public function getUnit(mixed $default = null): mixed
    {
        return $this->unit ?? $default;
    }

    public function getInformation(mixed $default = null): mixed
    {
        return $this->information ?? $default;
    }

    public function getReadonly(mixed $default = null): mixed
    {
        return $this->readonly ?? $default;
    }

    public function getMetadata(mixed $default = null): mixed
    {
        return $this->metadata ?? $default;
    }
}
