<?php

namespace Vnetby\Wptheme\Entities\Base;

/**
 * @template TModel = \Vnetby\Wptheme\Models\Model
 */
class DbResult
{
    public int $page;
    public int $perPage;
    public int $total;

    /**
     * @var TModel[]
     */
    public array $items;

    private $current = 0;

    function __construct(array $items, int $page, int $perPage, int $total)
    {
        $this->items = $items;
        $this->page = $page;
        $this->perPage = $perPage;
        $this->total = $total;
    }


    /**
     * @return ?TModel
     */
    function fetch()
    {
        $i = $this->current;
        if (isset($this->items[$i])) {
            $this->current++;
            return $this->items[$i];
        }
        return null;
    }


    /**
     * @return TModel[]
     */
    function getItems()
    {
        return $this->items;
    }


    function hasResults(): bool
    {
        return !!$this->items;
    }


    function getPage(): int
    {
        return $this->page;
    }


    function getPerPage(): int
    {
        return $this->perPage;
    }


    function getTotal(): int
    {
        return $this->total;
    }
}
