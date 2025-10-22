<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Support\Attribute;

use BrightLiu\LowCode\Support\Attribute\Foundation\ConvertAction;
use BrightLiu\LowCode\Support\Attribute\Foundation\Converted;
use BrightLiu\LowCode\Support\Attribute\Foundation\Converter;
use Illuminate\Filesystem\Filesystem;

class Conversion
{
    protected static string $converterNamespace = '';

    /**
     * 预设的Convert
     *
     * @var array<string,class-string<Converter>>|null
     */
    protected static ?array $presetConverterClassCollection = null;

    /**
     * 自定义的Convert
     *
     * @var array<string,class-string<Converter>>|null
     */
    protected ?array $convertClassCollection = null;

    public static function make(?array $convertClassCollection = null): static
    {
        $instance = new static();

        if (is_array($convertClassCollection)) {
            $instance->using($convertClassCollection);
        }

        return $instance;
    }

    /**
     * 获取转换数据
     */
    public function fetch(array $attributes, array $context = [], array $actions = ['*'], bool $realityKey = false): array
    {
        $convertibles = array_keys($this->getConvertClassCollection());

        $convertedData = [];

        foreach ($convertibles as $convertible) {
            $converted = $this->fetchOnce($convertible, $attributes, $context, $actions);

            $convertedData = array_merge($convertedData, $converted->toPrefixing($actions, $realityKey));
        }

        return array_merge($attributes, $convertedData);
    }

    /**
     * 获取单个属性的转换数据
     */
    public function fetchOnce(string $key, array $attributes, array $context = [], array $actions = ['*']): Converted
    {
        $converted = [];

        if (empty($key)) {
            return new Converted($key);
        }

        $converter = $this->resolveConverter($key, $attributes, $context);

        $actions = match (true) {
            empty($actions) || in_array('*', $actions, true) => ConvertAction::preset(),
            default => $actions,
        };

        foreach ($actions as $action) {
            $converted[$action] = $converter?->{$action}() ?? null;
        }

        return new Converted($key, ...$converted);
    }

    /**
     * 指定Convert
     */
    public function using(array $convertClassCollection, bool $combine = false): static
    {
        $resolved = [];

        foreach ($convertClassCollection as $key => $value) {
            if (is_numeric($key) && is_subclass_of($value, Converter::class)) {
                $resolved[$value::define()] = $value;
            } else {
                $resolved[$key] = $value;
            }
        }

        if (true === $combine) {
            $resolved = array_merge(self::$presetConverterClassCollection ??= $this->collectConverterClass(), $resolved);
        }

        $this->convertClassCollection = $resolved;

        return $this;
    }

    /**
     * 获取可用的Convert
     */
    protected function getConvertClassCollection(): array
    {
        return match (true) {
            is_null($this->convertClassCollection) => self::$presetConverterClassCollection ??= $this->collectConverterClass(),
            default => $this->convertClassCollection,
        };
    }

    /**
     * 解析构建Converter
     */
    protected function resolveConverter(string $key, array $attributes, array $context = []): ?Converter
    {
        $converters = $this->getConvertClassCollection();

        if (empty($converterClass = ($converters[$key] ?? null))) {
            return null;
        }

        /** @var class-string<Converter> $converterClass */
        return new $converterClass($attributes[$key] ?? null, $attributes, $context);
    }

    /**
     * 扫描收集Converters
     *
     * @return array<string,class-string<Converter>>
     */
    protected function collectConverterClass(): array
    {
        if (empty(self::$converterNamespace)) {
            return [];
        }

        // 根据命名空间，解析出对应路径
        $scanPath = str_replace('\\', '/', trim(str_replace('App\\', '', self::$converterNamespace), '\\'));

        $converterClass = [];

        collect((new Filesystem())
            ->allFiles(app_path($scanPath)))
            ->each(function (\SplFileInfo $file) use (&$converterClass) {
                try {
                    if ('php' != $file->getExtension()) {
                        return true;
                    }

                    $className = self::$converterNamespace . $file->getFilenameWithoutExtension();

                    if (class_exists($className)) {
                        /** @var Converter $className */
                        $converterClass[$className::define()] = $className;
                    }
                } catch (\Throwable $e) {
                }
            });

        return $converterClass;
    }

    /**
     * 注册转换器
     */
    public static function registerConverts(array $convertClass, bool $replace = false): void
    {
        $resolved = [];

        foreach ($convertClass as $key => $value) {
            if (is_numeric($key) && is_subclass_of($value, Converter::class)) {
                $resolved[$value::define()] = $value;
            } else {
                $resolved[$key] = $value;
            }
        }

        if (false === $replace && is_array(self::$presetConverterClassCollection)) {
            self::$presetConverterClassCollection = array_merge(
                self::$presetConverterClassCollection,
                $resolved
            );
        } else {
            self::$presetConverterClassCollection = $resolved;
        }
    }

    /**
     * 定义转换器命名空间
     * PS: 用于扫描收集Converters
     */
    public static function defineConverterNamespace(string $namespace): void
    {
        self::$converterNamespace = trim($namespace, '\\') . '\\';
    }
}
