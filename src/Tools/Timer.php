<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Tools;

/**
 * 计时器
 */
final class Timer
{
    private array $startTimes = [];

    /**
     * Start
     */
    public function start(): void
    {
        $this->startTimes[] = (float) hrtime(true);
    }

    /**
     * End
     */
    public function stop(): bool|int
    {
        if (empty($this->startTimes)) {
            return false;
        }

        return intval((hrtime(true) - array_pop($this->startTimes)) / 1000000);
    }
}
