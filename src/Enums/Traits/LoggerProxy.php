<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Enums\Traits;

use Psr\Log\LoggerInterface;

trait LoggerProxy
{
    /**
     * @param array $contextData
     *
     * @return LoggerInterface
     */
    public function make(array $contextData = []): LoggerInterface
    {
        if (empty(config("logging.channels.{$this->value}"))) {
            config(["logging.channels.{$this->value}" => $this->buildLoggerConfig($this->value)]);
        }

        return logs($this->value)->withContext($contextData);
    }

    /**
     * @param string $name
     * @param null|int $days
     *
     * @return array
     */
    protected function buildLoggerConfig(string $name, ?int $days = null): array
    {
        return [
            'driver' => 'daily',
            'path' => storage_path("logs/{$name}/daily.log"),
            'level' => (string) config('logging.default_level', 'debug'),
            'days' => (int) ($days ?? config('logging.default_daily_days', 7)),
        ];
    }

    /**
     * System is unusable.
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function emergency($message, array $context = []): void
    {
        $this->make()->emergency($message, $context);
    }

    /**
     * Action must be taken immediately.
     *
     * Example: Entire website down, database unavailable, etc. This should
     * trigger the SMS alerts and wake you up.
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function alert($message, array $context = []): void
    {
        $this->make()->alert($message, $context);
    }

    /**
     * Critical conditions.
     *
     * Example: Application component unavailable, unexpected exception.
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function critical($message, array $context = []): void
    {
        $this->make()->critical($message, $context);
    }

    /**
     * Runtime errors that do not require immediate action but should typically
     * be logged and monitored.
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function error($message, array $context = []): void
    {
        $this->make()->error($message, $context);
    }

    /**
     * Exceptional occurrences that are not errors.
     *
     * Example: Use of deprecated APIs, poor use of an API, undesirable things
     * that are not necessarily wrong.
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function warning($message, array $context = []): void
    {
        $this->make()->warning($message, $context);
    }

    /**
     * Normal but significant events.
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function notice($message, array $context = []): void
    {
        $this->make()->notice($message, $context);
    }

    /**
     * Interesting events.
     *
     * Example: User logs in, SQL logs.
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function info($message, array $context = []): void
    {
        $this->make()->info($message, $context);
    }

    /**
     * Detailed debug information.
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function debug($message, array $context = []): void
    {
        $this->make()->debug($message, $context);
    }

    /**
     * sprintf format info log.
     *
     * @param string $format
     * @param array $args
     * @param array $context
     *
     * @return void
     */
    public function sprintfInfo(string $format, array $args = [], array $context = []): void
    {
        $this->info(sprintf($format, ...$args), $context);
    }

    /**
     * sprintf format error log.
     *
     * @param string $format
     * @param array $args
     * @param array $context
     *
     * @return void
     */
    public function sprintfError(string $format, array $args = [], array $context = []): void
    {
        $this->error(sprintf($format, ...$args), $context);
    }
}
