<?php

namespace Vnetby\Wptheme\Traits\Config;

use Vnetby\Helpers\HelperArr;
use Vnetby\Helpers\HelperPath;

trait ConfigPathes
{

    /**
     * - Путь к папке с шаблонами html разметки относительно корня темы
     * @var string
     */
    protected string $pathTemplates = 'template-parts';

    /**
     * - Путь к папке с секциями html разметки относительно корня проекта
     * @var string
     */
    protected string $pathSections = 'template-sections';

    /**
     * - Путь к папке с кэшем относительно корня проекта
     * @var string
     */
    protected string $pathCache = 'wp-content/cache';

    /**
     * - Путь к папке с картинками относительно корня темы
     * @var string
     */
    protected string $pathImg = 'img';

    /**
     * - Путь к папке с svg относительно корня темы
     * @var string
     */
    protected string $pathSvg = 'img/svg';

    /**
     * - Путьт к папке с шаблонами писем относительно корня темы
     * @var string
     */
    protected string $pathEmailTemplates = 'template-parts/email';


    function themeUri(...$parts): string
    {
        return HelperPath::join(get_stylesheet_directory_uri(), ...$parts);
    }

    function themePath(...$parts): string
    {
        return HelperPath::join(get_stylesheet_directory(), ...$parts);
    }

    function libPath(...$parts): string
    {
        return HelperPath::join(realpath(__DIR__ . '/../../../'), ...$parts);
    }

    function libUri(...$parts): string
    {
        return HelperPath::pathToUrl($this->libPath(...$parts));
    }


    function setPathTemplates(string $pathTemplate)
    {
        $this->pathTemplates = $pathTemplate;
        return $this;
    }

    function getPathTemplates($abs = false): string
    {
        return !$abs ? $this->pathTemplates : $this->themePath($this->pathTemplates);
    }

    function setPathSections(string $pathSections)
    {
        $this->pathSections = $pathSections;
        return $this;
    }

    function getPathSections($abs = false): string
    {
        return !$abs ? $this->pathSections : $this->themePath($this->pathSections);
    }

    function setPathCache(string $pathCache)
    {
        $this->pathCache = $pathCache;
        return $this;
    }

    function getPathCache($abs = false): string
    {
        return !$abs ? $this->pathCache : HelperPath::abs($this->pathCache);
    }

    function setPathImg(string $pathImg)
    {
        $this->pathImg = $pathImg;
        return $this;
    }

    function getPathImg($abs = false): string
    {
        return !$abs ? $this->pathImg : $this->themePath($this->pathImg);
    }

    function setPathEmailTemplates(string $pathEmailTemplates)
    {
        $this->pathEmailTemplates = $pathEmailTemplates;
    }

    function getPathEmailTemplates($abs = false): string
    {
        return !$abs ? $this->pathEmailTemplates : $this->themePath($this->pathEmailTemplates);
    }

    /**
     * @param string $themeRelativePath
     * @return static
     */
    function setPathSvg(string $themeRelativePath)
    {
        $this->pathSvg = $themeRelativePath;
        return $this;
    }

    function getPathSvg($abs = true): string
    {
        return !$abs ? $this->pathSvg : $this->themePath($this->pathSvg);
    }
}
