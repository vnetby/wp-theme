<?php

namespace Vnetby\Wptheme\Traits\Config;


trait ConfigAjax
{
    protected string $ajaxUrl = '';

    protected string $captchaKey = '';

    protected string $captchaSecret = '';

    /**
     * @param string $ajaxUrl
     * @return static
     */
    function setAjaxUrl(string $ajaxUrl)
    {
        $this->ajaxUrl = $ajaxUrl;
        return $this;
    }

    function getAjaxUrl(): string
    {
        return $this->ajaxUrl;
    }


    function getCaptchaSecret(): string
    {
        return $this->captchaSecret;
    }

    function setCaptchaSecret(string $captchaSecret)
    {
        $this->captchaSecret = $captchaSecret;
        return $this;
    }

    function getCaptchaKey(): string
    {
        return $this->captchaKey;
    }

    /**
     * @param string $captchaKey
     * @return static
     */
    function setCaptchaKey(string $captchaKey)
    {
        $this->captchaKey = $captchaKey;
        return $this;
    }
}
