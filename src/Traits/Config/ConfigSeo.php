<?php

namespace Vnetby\Wptheme\Traits\Config;

use Vnetby\Wptheme\Seo;

trait ConfigSeo
{

    protected bool $seoEnabled = true;

    protected string $notFoundTitle;


    /**
     * @return static
     */
    function setSeoEnabled(bool $enable)
    {
        $this->seoEnabled = $enable;
        return $this;
    }

    function isSeoEnabled(): bool
    {
        return $this->seoEnabled;
    }

    protected function registerSeo()
    {
        if ($this->isSeoEnabled()) {
            Seo::setupSeoSettings();
            Seo::setup();
        }
    }

    /**
     * @return static
     */
    function setNotFoundTitle(string $title)
    {
        $this->notFoundTitle = $title;
        return $this;
    }

    function getNotFoundTitle(): string
    {
        return isset($this->notFoundTitle) ? $this->notFoundTitle : __('Страница не найдена', 'vnet');
    }
}
