<?php

namespace Vnetby\Wptheme\Traits\Config;

use Vnetby\Wptheme\Seo;

trait ConfigSeo
{

    protected bool $pagesSeo = true;

    /**
     * @return static
     */
    function enablePagesSeo(bool $enable)
    {
        $this->pagesSeo = $enable;
        return $this;
    }

    function isPagesSeoEnabled(): bool
    {
        return $this->pagesSeo;
    }

    protected function registerSeo()
    {
        if ($this->isPagesSeoEnabled()) {
            Seo::addPostsSeo();
        }
    }
}
