<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Entities;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

abstract class Entity implements Arrayable, \JsonSerializable
{
    protected static ?string $reflectionCached = null;

    public function __construct(
        protected array $items = []
    ) {
        $this->items = array_merge($this->initialize(), $this->items);
    }

    /**
     * @see \JsonSerializable
     */
    public function jsonSerialize(): mixed
    {
        return $this->items;
    }

    /**
     * @param self|Model|array $data
     */
    public static function make($data = [], bool $preset = false): static
    {
        return tap(
            new static(match (true) {
                $data instanceof self, $data instanceof Model => $data->toArray(),
                default => $data,
            }),
            function (Entity $instance) use ($preset) {
                if ($preset) {
                    $instance->fill(
                        attributes: array_merge($instance->loadPresetProperties(), $instance->getItems()),
                        override: true
                    );
                }
            }
        );
    }

    public static function collection(array $data, bool $preset = false): Collection
    {
        return collect($data)->map(fn ($item) => static::make($item, $preset));
    }

    protected function initialize(): array
    {
        return [];
    }

    /**
     * 加载预设属性值
     */
    protected function loadPresetProperties(): array
    {
        try {
            if (is_null($docComment = static::$reflectionCached ?? null)) {
                $docComment = static::$reflectionCached = (string) (new \ReflectionClass($this))->getDocComment();
            }

            preg_match_all('/@property\s+([^\s]+)\s+\$([^\s]+)/', $docComment, $matches);
            $properties = array_combine($matches[2], $matches[1]);

            return array_map(
                fn ($type) => match (mb_strtolower($type)) {
                    'array' => [],
                    'int' => 0,
                    'float' => 0,
                    'bool' => false,
                    'string' => '',
                    default => null
                },
                $properties
            );
        } catch (\Throwable) {
            return [];
        }
    }

    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * Get an attribute from the bean.
     */
    public function getAttribute(string $key, mixed $default = null): mixed
    {
        if (empty($key)) {
            return $default;
        }

        return $this->items[$key] ?? $default;
    }

    public function unsetAttribute(string $key): void
    {
        unset($this->items[$key]);
    }

    /**
     * Set a given attribute on the bean.
     */
    public function setAttribute(string $key, mixed $value): void
    {
        $this->items[$key] = $value;
    }

    public function fill(array $attributes, bool $override = false): static
    {
        $this->items = match (true) {
            $override => $attributes,
            default => array_merge($this->items, $attributes),
        };

        return $this;
    }

    /**
     * @param array|mixed $attributes
     */
    public function only(mixed $attributes, bool $filter = false): array
    {
        $attributes = (array) $attributes;

        $results = [];

        foreach ($attributes as $attribute => $defaultValue) {
            if (is_numeric($attribute)) {
                $attribute = $defaultValue;
                $defaultValue = null;
            }

            $results[$attribute] = $this->getAttribute($attribute, $defaultValue);
        }

        return match ($filter) {
            true => array_filter($results, fn ($value) => !is_null($value)),
            default => $results,
        };
    }

    public function clone(): static
    {
        return static::make($this);
    }

    public function toArray(): array
    {
        return $this->items;
    }

    public function __get($name)
    {
        return $this->getAttribute($name);
    }

    /**
     * @return bool
     */
    public function __isset($name)
    {
        return !is_null($this->items[$name] ?? null);
    }

    /**
     * @return void
     */
    public function __set($name, $arguments)
    {
        $this->setAttribute($name, $arguments);
    }

    public function __call($name, $arguments)
    {
        if (str_starts_with($name, 'get')) {
            return $this->getAttribute(Str::snake(lcfirst(mb_substr($name, 3))));
        }

        if (str_starts_with($name, 'set')) {
            $this->setAttribute(Str::snake(lcfirst(mb_substr($name, 3))), $arguments[0]);

            return;
        }

        return null;
    }
}
