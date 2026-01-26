<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Services\Contracts;

use BrightLiu\LowCode\Core\Abstracts\QueryEngineAbstract;

interface ILowCodeQueryBuilder
{

    /**
     * Build the query with provided parameters and config.
     */
    public function __invoke(
        QueryEngineAbstract $queryEngine,
        array $queryParams,
        array $config,
        string $bizSceneTable
    ): QueryEngineAbstract;

    /**
     * Prepare filters and legacy adjustments.
     */
    public function prepare(): array;

    /**
     * Declare preparation actions.
     */
    public function prepareActions(): array;

    /**
     * Attach AI generated filters.
     */
    public function prepareAiFilter(array $filters): array;

    /**
     * Build base relations for the query.
     */
    public function relationQueryEngine(array $filters): void;

    /**
     * Merge preset conditions from config.
     */
    public function mergeConfigPresetCondition(array $filters): array;

    /**
     * Apply filters to the query engine.
     */
    public function applyFilters(array $filters): void;

    /**
     * Apply order by rules.
     */
    public function applyOrderBy(): void;

    /**
     * Resolve data permission conditions.
     */
    public function resolveDataPermissionCondition(): array;

    /**
     * Attach data permission conditions to query.
     */
    public function attachDataPermissionCondition(): void;
}
