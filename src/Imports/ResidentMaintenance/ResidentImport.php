<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Imports\ResidentMaintenance;

use Gupo\BetterLaravel\Exceptions\ServiceException;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use BrightLiu\LowCode\Traits\Context\WithContext;

final class ResidentImport implements ToCollection
{
    use WithContext;

    protected Collection $handledData;

    /**
     * @return void
     *
     * @throws ServiceException
     */
    public function collection(Collection $collection)
    {
        $collection = $collection->except([0]);

        $this->handledData = $this->handle($collection);
    }

    protected function handle(Collection $rows): Collection
    {
        $rows = $rows->values(); // 重置索引
        if ($rows->count() <= 1) {
            throw new ServiceException('没有可导入的数据');
        }

        $fields = $rows->first()->toArray();

        return $rows->slice(1)->map(fn ($row) => array_combine($fields, $row->toArray()))->filter()->values();
    }

    public function getResult(): Collection
    {
        return $this->handledData;
    }
}
