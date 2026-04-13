<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Services\LowCode\ColumnAppender;

use BrightLiu\LowCode\Enums\Foundation\Logger;
use BrightLiu\LowCode\Services\LowCode\ColumnAppender\Columns\CrowdTypeColumn;
use BrightLiu\LowCode\Services\LowCode\ColumnAppender\Columns\FollowStatusColumn;
use BrightLiu\LowCode\Services\LowCode\ColumnAppender\Columns\PatientTagsColumn;
use BrightLiu\LowCode\Services\QueryEngineService;
use BrightLiu\LowCode\Traits\InstanceMake;
use Illuminate\Support\Collection;

/**
 * TODO: 写法待完善
 */
class AppenderManager
{
    use InstanceMake;

    /**
     * @var array{class-string<IColumn>}
     */
    protected static array $appender = [
        CrowdTypeColumn::class,
        FollowStatusColumn::class,
        PatientTagsColumn::class,
    ];

    public function handle(QueryEngineService $queryEngine, Collection $items, ?array $columnKeys = null): Collection
    {
        foreach (self::resolveAppenders($columnKeys) as $appender) {
            try {
                $items = (new $appender())(clone $queryEngine, $items);
            } catch (\Throwable $e) {
                Logger::LOW_CODE_APPENDER->error(sprintf('%s 异常', $appender), ['exception' => $e->getMessage()]);
            }
        }

        return $items;
    }

    public static function register(array|string $appender): void
    {
        if (is_string($appender)) {
            $appender = [$appender];
        }

        self::$appender = array_merge(self::$appender, $appender);
    }

    public static function collectMetadata(): array
    {
        $metadata = [];

        foreach (self::$appender as $appender) {
            try {
                if (method_exists($appender, 'metadata')) {
                    $metadata[] = $appender::metadata();
                }
            } catch (\Throwable $e) {
                Logger::LOW_CODE_APPENDER->error(sprintf('%s metadata 异常', $appender), ['exception' => $e->getMessage()]);
            }
        }

        return array_map(
            fn ($item) => [
                'id' => $item['group_id'] ?? '',
                'name' => $item['group_name'] ?? '',
                'columns' => [
                    [
                        'id' => $item['id'] ?? '',
                        'name' => $item['name'] ?? '',
                        'type' => $item['type'] ?? 'string',
                        'column' => $item['column'] ?? '',
                    ],
                ],
            ],
            $metadata
        );
    }

    /**
     * @return array<class-string<IColumn>>
     */
    protected static function resolveAppenders(?array $columnKeys = null): array
    {
        if (null === $columnKeys) {
            return self::$appender;
        }

        if ([] === $columnKeys) {
            return [];
        }

        $columnKeys = array_values(array_unique(array_filter($columnKeys, fn ($item) => is_string($item) && '' !== $item)));

        if ([] === $columnKeys) {
            return [];
        }

        return array_values(array_filter(self::$appender, function (string $appender) use ($columnKeys) {
            try {
                $metadata = method_exists($appender, 'metadata') ? $appender::metadata() : [];
                $columnName = $metadata['column'] ?? $appender::columnName();

                return in_array($columnName, $columnKeys, true);
            } catch (\Throwable $e) {
                Logger::LOW_CODE_APPENDER->error(sprintf('%s resolve 异常', $appender), ['exception' => $e->getMessage()]);

                return false;
            }
        }));
    }
}
