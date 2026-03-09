<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Services\LowCode\Tools;

use BrightLiu\LowCode\Enums\Foundation\Logger;
use Illuminate\Database\Query\Builder as QueryBuilder;

final class EmpiFullFilterTools
{
    /**
     * 为了减少连表的查询性能消耗，根据连表情况，确保给所有表都带上empi的过滤条件
     */
    public function __invoke(QueryBuilder $builder, array $fullEmpiFields = [], array $empiValues = []): QueryBuilder
    {
        if (!$this->isEnabled()) {
            return $builder;
        }

        if (empty($fullEmpiFields)) {
            return $builder;
        }

        $realBuilder = null;

        try {
            $realBuilder = clone $builder;

            $empiValues = $empiValues ?: $this->extractEmpiValuesFromWheres($builder->wheres ?? []);
            if (empty($empiValues)) {
                return $builder;
            }

            $this->removeEmpiConditions($builder);

            foreach ($fullEmpiFields as $empiField) {
                $builder->whereIn($empiField, $empiValues);
            }
        } catch (\Throwable $e) {
            Logger::LARAVEL->error('EmpiFullFilterService failed to process query builder');

            return $realBuilder ?: $builder;
        }

        return $builder;
    }

    private function isEnabled(): bool
    {
        return (bool) config('low-code.optimization.empi_full_search_enabled', false);
    }

    private function removeEmpiConditions(QueryBuilder $builder): void
    {
        [$filteredWheres] = $this->filterEmpiConditions($builder->wheres ?? []);

        $builder->wheres = $filteredWheres;
        $builder->setBindings($this->collectWhereBindings($filteredWheres), 'where');
    }

    /**
     * @param array<int, array<string, mixed>> $wheres
     *
     * @return array{0: array<int, array<string, mixed>>, 1: bool}
     */
    private function filterEmpiConditions(array $wheres): array
    {
        $filtered = [];
        $removed = false;

        foreach ($wheres as $where) {
            if (!is_array($where)) {
                $filtered[] = $where;
                continue;
            }

            $type = (string) ($where['type'] ?? '');

            if ('Nested' === $type && ($where['query'] ?? null) instanceof QueryBuilder) {
                [$nestedWheres, $nestedRemoved] = $this->filterEmpiConditions($where['query']->wheres ?? []);

                $where['query']->wheres = $nestedWheres;
                $where['query']->setBindings($this->collectWhereBindings($nestedWheres), 'where');

                if (!empty($nestedWheres)) {
                    $where['query'] = $where['query'];
                    $filtered[] = $where;
                } else {
                    $removed = true;
                }

                $removed = $removed || $nestedRemoved;
                continue;
            }

            if ($this->isEmpiWhere($where)) {
                $removed = true;
                continue;
            }

            $filtered[] = $where;
        }

        return [$filtered, $removed];
    }

    /**
     * @param array<string, mixed> $where
     */
    private function isEmpiWhere(array $where): bool
    {
        $column = (string) ($where['column'] ?? '');
        if (!$this->isEmpiColumn($column)) {
            return false;
        }

        $type = (string) ($where['type'] ?? '');
        if ('In' === $type) {
            return true;
        }

        return 'Basic' === $type && (($where['operator'] ?? '=') === '=');
    }

    /**
     * Rebuild where bindings from current where tree to keep SQL placeholders aligned.
     *
     * @param array<int, array<string, mixed>> $wheres
     *
     * @return array<int, mixed>
     */
    private function collectWhereBindings(array $wheres): array
    {
        $bindings = [];

        foreach ($wheres as $where) {
            if (!is_array($where)) {
                continue;
            }

            $type = (string) ($where['type'] ?? '');

            if ('Basic' === $type) {
                if (array_key_exists('value', $where)) {
                    $bindings[] = $where['value'];
                }
                continue;
            }

            if ('In' === $type || 'NotIn' === $type) {
                $values = $where['values'] ?? [];
                if (is_array($values)) {
                    $bindings = array_merge($bindings, array_values($values));
                }
                continue;
            }

            if ('Between' === $type || 'NotBetween' === $type) {
                $values = $where['values'] ?? [];
                if (is_array($values)) {
                    $bindings = array_merge($bindings, array_values($values));
                }
                continue;
            }

            if ('Nested' === $type && ($where['query'] ?? null) instanceof QueryBuilder) {
                $bindings = array_merge($bindings, $this->collectWhereBindings($where['query']->wheres ?? []));
                continue;
            }

            if (('Exists' === $type || 'NotExists' === $type) && ($where['query'] ?? null) instanceof QueryBuilder) {
                $bindings = array_merge($bindings, $where['query']->getBindings());
                continue;
            }

            if ('Raw' === $type) {
                $rawBindings = $where['bindings'] ?? [];
                if (is_array($rawBindings)) {
                    $bindings = array_merge($bindings, $rawBindings);
                }
                continue;
            }

            if (in_array($type, ['Date', 'Time', 'Day', 'Month', 'Year'], true) && array_key_exists('value', $where)) {
                $bindings[] = $where['value'];
            }
        }

        return $bindings;
    }

    /**
     * Recursively parse where clauses and collect empi values from '=' and 'IN' conditions.
     *
     * @param array<int, array<string, mixed>> $wheres
     *
     * @return array<int, string>
     */
    private function extractEmpiValuesFromWheres(array $wheres): array
    {
        $values = [];

        foreach ($wheres as $where) {
            if (!is_array($where)) {
                continue;
            }

            $type = (string) ($where['type'] ?? '');

            if ('Nested' === $type && ($where['query'] ?? null) instanceof QueryBuilder) {
                $nestedValues = $this->extractEmpiValuesFromWheres($where['query']->wheres ?? []);
                $values = array_merge($values, $nestedValues);
                continue;
            }

            $column = (string) ($where['column'] ?? '');
            if (!$this->isEmpiColumn($column)) {
                continue;
            }

            if ('In' === $type) {
                $inValues = $where['values'] ?? [];
                if (is_array($inValues)) {
                    $values = array_merge($values, $inValues);
                }
                continue;
            }

            if ('Basic' === $type && (($where['operator'] ?? '=') === '=')) {
                $values[] = (string) ($where['value'] ?? '');
            }
        }

        $values = array_values(array_unique(array_filter(array_map('strval', $values), static fn ($v) => '' !== $v)));

        return $values;
    }

    private function isEmpiColumn(string $column): bool
    {
        return 'empi' === $column || 1 === preg_match('/(^|\.)empi$/', $column);
    }
}
