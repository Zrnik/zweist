<?php

declare(strict_types=1);

namespace Zrnik\Zweist\Content\Pagination;

use InvalidArgumentException;
use Nette\Utils\Paginator;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;

#[Schema(description: 'Calculated page values.')]
class PaginationData
{
    #[Property]
    public int $page;

    #[Property]
    public int $pageCount;

    #[Property]
    public int $firstPage;

    #[Property]
    public int $lastPage;

    #[Property]
    public int $itemsPerPage;

    #[Property]
    public int $itemCount;

    #[Property]
    public bool $hasNextPage;

    #[Property]
    public bool $hasPreviousPage;

    #[Property]
    public int $firstItemIndex;

    #[Property]
    public int $lastItemIndex;

    public function __construct(
        int $page,
        int $itemsPerPage,
        int $itemCount,
    )
    {
        $paginator = new Paginator();
        $paginator->setPage($page);
        $paginator->setItemsPerPage($itemsPerPage);
        $paginator->setItemCount($itemCount);

        $this->page = $paginator->getPage();
        $this->pageCount = $paginator->getPageCount() ?? throw new InvalidArgumentException('page count unset');

        $this->firstPage = $paginator->getFirstPage();
        $this->lastPage = $paginator->getLastPage() ?? throw new InvalidArgumentException('last page unset');

        $this->itemsPerPage = $paginator->getItemsPerPage();
        $this->itemCount = $paginator->getItemCount() ?? throw new InvalidArgumentException('item count unset');

        $this->hasNextPage = ! $paginator->isLast();
        $this->hasPreviousPage = ! $paginator->isFirst();

        $this->firstItemIndex = $paginator->getFirstItemOnPage();
        $this->lastItemIndex = $paginator->getLastItemOnPage();
    }
}
