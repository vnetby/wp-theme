<?php

namespace Vnetby\Wptheme\Models;

class DbResult
{
    public int $page;
    public int $perPage;
    public int $total;

    /**
     * @var Model[]
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


    function fetch(): ?Model
    {
        $i = $this->current;
        if (isset($this->items[$i])) {
            $this->current++;
            return $this->items[$i];
        }
        return null;
    }
}
