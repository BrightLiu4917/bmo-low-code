<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Support\Attribute\Contracts;

interface Convertable
{
    public function getter(): mixed;

    public function setter(mixed $value): void;
}
