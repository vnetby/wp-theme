<?php

namespace Vnetby\Wptheme\Traits\Config;

use Vnetby\Helpers\HelperArr;
use Vnetby\Helpers\HelperPath;

trait ConfigTheme
{
    /**
     * - Используется для генерации миниатюр
     * @var array<string,array{width: int, height: int, crop: bool}>
     */
    protected array $imageSizes = [];

    /**
     * - Массив переменных для фронта
     * @var array<string,mixed>
     */
    protected array $frontVars = [];

    /**
     * - Массив областей меню
     * @var array<string,string>
     */
    protected array $menus = [];

    /**
     * - Массив стилей для фронта
     * @var array<string,string>
     */
    protected array $frontStyles = [];

    /**
     * - Массив скриптов для фронта
     * @var array<string,string>
     */
    protected array $frontScripts = [];

    /**
     * - Массив стилей для админки
     * @var array<string,string>
     */
    protected array $adminStyles = [];

    /**
     * - Массив скриптов для админки
     * @var array<string,string>
     */
    protected array $adminScripts = [];

    /**
     * - Массив стилей для текстового редактора
     * @var string[]
     */
    protected array $mceCss = [];

    /**
     * - Аватарка для root пользователя
     */
    protected string $rootAvatar = '';


    /**
     * @param string $key
     * @param integer $width
     * @param integer $height
     * @param boolean $crop
     * @return static
     */
    function addImageSize(string $key, int $width, int $height, bool $crop = true)
    {
        $this->imageSizes[$key] = [$width, $height, $crop];
        return $this;
    }

    /**
     * @param string $key
     * @param mixed $value
     * @return static
     */
    function addFrontVar(string $key, $value)
    {
        $this->frontVars[$key] = $value;
        return $this;
    }

    /**
     * @param string $key
     * @param string $desc
     * @return static
     */
    function addMenu(string $key, string $desc)
    {
        $this->menus[$key] = $desc;
        return $this;
    }

    /**
     * - Добавляет относительно корня проекта
     * @param string $handle
     * @param string $src
     * @param boolean $inFooter
     * @param array $deps
     * @return static
     */
    function addAdminScript(string $handle, string $src, $inFooter = true, $deps = [])
    {
        $this->adminScripts[$handle] = [
            'src' => $src,
            'inFooter' => $inFooter,
            'deps' => $deps
        ];
        return $this;
    }

    /**
     * - Добавляет относительно корня темы
     * @param string $handle
     * @param string $src
     * @param boolean $inFooter
     * @param array $deps
     * @return static
     */
    function addAdminScriptTheme(string $handle, string $src, $inFooter = true, $deps = [])
    {
        return $this->addAdminScript($handle, $this->themeUri($src), $inFooter, $deps);
    }

    /**
     * - Добавляет относительно корня проекта
     * @param string $handle
     * @param string $src
     * @param array $deps
     * @param string $media
     * @return static
     */
    function addAdminStyle(string $handle, string $src, $deps = [], $media = 'all')
    {
        $this->adminStyles[$handle] = [
            'src' => $src,
            'deps' => $deps,
            'media' => $media
        ];
        return $this;
    }

    /**
     * - Добавляет относительно корня темы
     * @param string $handle
     * @param string $src
     * @param array $deps
     * @param string $media
     * @return static
     */
    function addAdminStyleTheme(string $handle, string $src, $deps = [], $media = 'all')
    {
        return $this->addAdminStyle($handle, $this->themeUri($src), $deps, $media);
    }

    /**
     * - Добавляет относительно корня проекта
     * @param string $handle
     * @param string $src
     * @param boolean $inFooter
     * @param array $deps
     * @return static
     */
    function addFrontScript(string $handle, string $src, $inFooter = true, $deps = [])
    {
        $this->frontScripts[$handle] = [
            'src' => $src,
            'inFooter' => $inFooter,
            'deps' => $deps
        ];
        return $this;
    }

    /**
     * - Добавляет относительно корня темы
     * @param string $handle
     * @param string $src
     * @param boolean $inFooter
     * @param array $deps
     * @return static
     */
    function addFrontScriptTheme(string $handle, string $src, $inFooter = true, $deps = [])
    {
        return $this->addFrontScript($handle, $this->themeUri($src), $inFooter, $deps);
    }

    /**
     * - Добавляет относительно корня проекта
     * @param string $handle
     * @param string $src
     * @param array $deps
     * @param string $media
     * @return static
     */
    function addFrontStyle(string $handle, string $src, $deps = [], $media = 'all')
    {
        $this->frontStyles[$handle] = [
            'src' => $src,
            'deps' => $deps,
            'media' => $media
        ];
        return $this;
    }

    /**
     * - Добавляет относительно корня темы
     * @param string $handle
     * @param string $src
     * @param array $deps
     * @param string $media
     * @return static
     */
    function addFrontStyleTheme(string $handle, string $src, $deps = [], $media = 'all')
    {
        return $this->addFrontStyle($handle, $this->themeUri($src), $deps, $media);
    }

    /**
     * - Добавляет относительно корня проекта
     * @param string $src
     * @return static
     */
    function addMceCss(string $src)
    {
        $this->mceCss[] = $src;
        return $this;
    }

    /**
     * - Добавляет относительно корня темы
     * @param string $src
     * @return static
     */
    function addMceCssTheme(string $src)
    {
        return $this->addMceCss($this->themeUri($src));
    }

    /**
     * - Добавляет относительно корня проекта
     * @param string $src
     * @return static
     */
    function setRootAvatar(string $src)
    {
        $this->rootAvatar = $src;
        return $this;
    }

    /**
     * - Добавляет относительно корня темы
     * @param string $src
     * @return static
     */
    function setRootAvatarTheme(string $src)
    {
        return $this->setRootAvatar($this->themeUri($src));
    }
}
