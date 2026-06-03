<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Support\Attribute;

use BrightLiu\LowCode\Support\Attribute\Converters\DynamicConverter;
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

    /**
     * 默认上下文（通过 withContext() 设置，合并到每次 fetch/fetchOnce 的 context 中）
     */
    protected array $defaultContext = [];

    /**
     * 是否启用 fallback 模式（无匹配 Converter 时走 DynamicConverter）
     */
    protected bool $fallbackEnabled = false;

    public static function make(?array $convertClassCollection = null): static
    {
        $instance = new static();

        if (is_array($convertClassCollection)) {
            $instance->using($convertClassCollection);
        }

        return $instance;
    }

    /**
     * 设置默认上下文
     */
    public function withContext(array $context): static
    {
        $this->defaultContext = $context;

        return $this;
    }

    /**
     * 启用/禁用 fallback 模式
     * 启用后，无匹配 Converter 的字段将走 DynamicConverter 统一回退处理
     */
    public function withFallback(bool $enabled = true): static
    {
        $this->fallbackEnabled = $enabled;

        return $this;
    }

    /**
     * 获取当前已注册的所有字段 key
     *
     * @return array<int,string>
     */
    public function getRegisteredKeys(): array
    {
        return array_keys($this->getConvertClassCollection());
    }

    /**
     * 获取需要获取 API 元信息的字段 keys
     * 排除 fetchFieldMeta() 返回 false 的 Converter（如计算/组合字段）
     *
     * @return array<int,string>
     */
    public function getFieldKeysForMeta(): array
    {
        $keys = [];

        foreach ($this->getConvertClassCollection() as $key => $converterClass) {
            /** @var class-string<Converter> $converterClass */
            if ($converterClass::fetchFieldMeta()) {
                $keys[] = $key;
            }
        }

        return $keys;
    }

    /**
     * 获取转换数据
     */
    public function fetch(array $attributes, array $context = [], array $actions = ['*'], bool $realityKey = false, array $convertibles = []): array
    {
        $context = array_merge($this->defaultContext, $context);

        // 当 $convertibles 非空时，优先遍历指定字段（允许处理未注册 Converter 的非枚举字段）
        $convertibles = !empty($context) && !empty($convertibles) ? $convertibles : array_keys($this->getConvertClassCollection());

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

        $context = array_merge($this->defaultContext, $context);

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
            $resolved = array_merge($this->convertClassCollection ?? [], self::$presetConverterClassCollection ??= $this->collectConverterClass(), $resolved);
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

        if (!empty($converterClass = ($converters[$key] ?? null))) {
            /** @var class-string<Converter> $converterClass */
            return new $converterClass($attributes[$key] ?? null, $attributes, $context);
        }

        // Fallback: 非 converter 注册字段走 DynamicConverter
        if ($this->fallbackEnabled) {
            return new DynamicConverter($key, $attributes[$key] ?? null, $attributes, $context);
        }

        return null;
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
