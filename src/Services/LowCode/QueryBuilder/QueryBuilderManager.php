<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Services\LowCode\QueryBuilder;

class QueryBuilderManager
{
    public static function mapping(): array
    {
        return [
            'default' => DefaultQueryBuilder::class,
            'mysql' => MysqlQueryBuilder::class,
            'exclude_exited' => ExcludeExitedQueryBuilder::class,
            'exclude_exited_mysql' => ExcludeExitedMysqlQueryBuilder::class,
        ];
    }

    public static function resolve(?string $default = null): string
    {
        if (empty($builderClass = config('low-code.custom-query.builder', ''))) {
            return $default ?: DefaultQueryBuilder::class;
        }

        $mapping = self::mapping();

        return (string) ($mapping[$builderClass] ?? $builderClass);
    }
}
