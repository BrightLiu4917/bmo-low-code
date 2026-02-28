<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Services\LowCode\ColumnAppender\Columns;

use BrightLiu\LowCode\Services\LowCode\ColumnAppender\IColumn;
use BrightLiu\LowCode\Services\QueryEngineService;
use Illuminate\Support\Collection;

abstract class BasicColumn implements IColumn
{
    public function __invoke(QueryEngineService $queryEngine, Collection $items): Collection
    {
        if (empty($items)) {
            return $items;
        }

        $sources = $this->preload($queryEngine, $items);

        $columnName = static::columnName();

        return $items->map(function ($item) use ($columnName, $sources) {
            $item->{$columnName} = $this->handleItem($item, $sources);

            return $item;
        });
    }

    public function preload(QueryEngineService $queryEngine, Collection $items): mixed
    {
        return null;
    }

    public function handleItem($item, $sources): mixed
    {
        return null;
    }
}
