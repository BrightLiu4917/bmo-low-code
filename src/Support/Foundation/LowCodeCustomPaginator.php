<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Support\Foundation;

use Gupo\BetterLaravel\Contracts\CustomPaginatorContract;
use Gupo\BetterLaravel\Database\CustomPaginator;
use Illuminate\Pagination\Paginator;

class LowCodeCustomPaginator extends CustomPaginator implements CustomPaginatorContract
{
    /**
     * @param Paginator $paginate 原始分页对象
     * @param bool $fetchAll 标识是否取回所有数据
     *
     * @return void
     */
    public function __construct(Paginator $paginate, bool $fetchAll = false)
    {
        parent::__construct($paginate, $fetchAll);

        $this->hasMore = $paginate->hasMorePages();
    }

    /**
     * **重写方法**
     *
     * 重新定义分页内容的响应格式
     */
    public function toArray(): array
    {
        return match ($this->fetchAll) {
            true => ['list' => $this->getCollection()],
            default => [
                'paginate' => [
                    'current_page' => (int) $this->currentPage(),
                    'current_count' => (int) $this->getCollection()->count(),
                    'page_size' => (int) $this->perPage(),
                    'has_more' => $this->hasMorePages() ? 1 : 0,
                ],
                'list' => $this->getCollection(),
            ]
        };
    }
}
