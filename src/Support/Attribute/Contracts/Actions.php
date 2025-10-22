<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Support\Attribute\Contracts;

interface Actions
{
    /**
     * 值
     */
    public function value(): mixed;

    /**
     * 变体
     */
    public function variant(): mixed;

    /**
     * 单位
     */
    public function unit(): string;

    /**
     * 信息描述
     */
    public function information(): string;

    /**
     * 是否只读
     */
    public function readonly(): bool;

    /**
     * 元信息
     */
    public function metadata(): array;
}
