<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Services\LowCode\ColumnAppender;

use BrightLiu\LowCode\Services\QueryEngineService;
use Illuminate\Support\Collection;

interface IColumn
{
    public function __invoke(QueryEngineService $queryEngine, Collection $items): Collection;

    public static function metadata(): array;

    public static function columnName(): string;
}
