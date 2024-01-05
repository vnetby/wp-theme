<?php

namespace Vnetby\Wptheme\Front;

use Error;
use Vnetby\Helpers\HelperArr;
use Vnetby\Helpers\HelperPath;
use Vnetby\Wptheme\Container;

class Template
{

    /**
     * @var static[]
     */
    private static $instances = [];

    protected array $args = [];

    protected string $html;

    protected string $file;

    protected string $key;

    protected int $ttl = 0;


    protected function __construct(string $file = '', array $args = [], string $key = '', int $ttl = 0)
    {
        $this->key = $key;
        $this->args = $args;
        $this->file = $file;
        $this->ttl = $ttl;

        $this->html = $this->fetchHtml();
    }


    /**
     * - Подключает файл из папки шаблонов
     * @param string $name относительный путь к файлу без расширения
     * @param array|callable $args аргументы шаблона
     * @return self
     */
    static function getTemplate(string $name, $args = [], int $ttl = 0)
    {
        $file = HelperPath::join(Container::getLoader()->getPathTemplates(true), $name . '.php');
        return self::getFile($file, $args, $ttl);
    }

    static function theTemplate(string $name, $args = [], int $ttl = 0)
    {
        self::getTemplate($name, $args, $ttl)->render();
    }


    /**
     * - Подключает файл из папки секции
     * @param string $name относительный путь к файлу без расширения
     * @param array|callable $args аргументы шаблона
     * 
     * @return self
     */
    static function getSection(string $name, $args = [], $ttl = 0)
    {
        $file = HelperPath::join(Container::getLoader()->getPathSections(true), $name . '.php');
        return self::getFile($file, $args, $ttl);
    }

    static function theSection(string $name, $args = [], int $ttl = 0)
    {
        self::getSection($name, $args, $ttl)->render();
    }


    /**
     * - Подключает файл из папки с шаблонами писем
     * @param string $name относительный путь к файлу без расширения
     * @param array|callable $args аргументы шаблона
     * 
     * @return self
     */
    static function getEmailTemplate(string $name, $args = [])
    {
        $file = HelperPath::join(Container::getLoader()->getPathEmailTemplates(true), $name . '.php');
        return self::getFile($file, $args);
    }

    static function theEmailTemplate(string $name, $args = [])
    {
        return self::getEmailTemplate($name, $args)->render();
    }


    /**
     * - Подключает произвольный файл
     * @param string $filePath
     * @param array|callable $args аргументы шаблона
     * @param integer $ttl
     * @return ?static
     */
    static function getFile(string $filePath, $args = [], $ttl = 0)
    {
        if (!file_exists($filePath)) {
            throw new Error("File {$filePath} does not exists");
        }

        if (is_callable($args)) {
            $args = call_user_func($args);
        }

        $key = md5($filePath . ':' . serialize($args));

        if (!isset(self::$instances[$key])) {
            self::$instances[$key] = new self($filePath, $args, $key, $ttl);
        }

        return self::$instances[$key];
    }

    static function theFile(string $filePath, $args = [], $ttl = 0)
    {
        self::getFile($filePath, $args, $ttl)->render();
    }



    function __toString()
    {
        echo $this->html;
    }


    protected function fetchHtml(): string
    {
        $str = '';
        $relPath = HelperPath::pathToUrl($this->file);

        if (is_user_logged_in()) {
            $str .= "\r\n<!-- [START_TEMPLATE key:{$this->key}; file:{$relPath}] -->\r\n";
        }

        $str .= $this->fetchHtmlContent();

        if (is_user_logged_in()) {
            $str .= "\r\n<!-- [END_TEMPLATE key:{$this->key}; file:{$relPath}]] -->\r\n";
        }

        return $str;
    }


    protected function fetchHtmlContent(): string
    {
        if (!$this->ttl || is_user_logged_in()) {
            return $this->execTemplate();
        }

        $cachePath = Container::getLoader()->getPathCache(true) . '/templates';

        if (!file_exists($cachePath)) {
            mkdir($cachePath, 0777, true);
        }

        $cacheFile = $cachePath . '/' . $this->key;

        if (file_exists($cacheFile) && time() - filemtime($cacheFile) < $this->ttl) {
            return file_get_contents($cacheFile);
        }

        $content = $this->execTemplate();

        file_put_contents($cacheFile, $content);

        return $content;
    }


    protected function execTemplate()
    {
        ob_start();
        require $this->file;
        return ob_get_clean();
    }


    function getHtml()
    {
        return $this->html;
    }


    function getArg($key, $def = null, $checkEmpty = false)
    {
        $res = HelperArr::get($this->args, $key, $def);
        if (!$checkEmpty) {
            return $res;
        }
        return $res ? $res : $def;
    }


    function render()
    {
        echo $this->html;
    }


    function getSvg(string $name, bool $src = false): string
    {
        $file = Container::getLoader()->getPathSvg(true) . '/' . $name . '.svg';

        if (!file_exists($file)) {
            return '';
        }

        if ($src) {
            return HelperPath::pathToUrl($file);
        }

        $file = file_get_contents($file);
        $file = str_replace('<svg', '<svg data-name="' . $name . '"', $file);

        return $file;
    }


    function getSvgImg(string $name): string
    {
        $src = $this->getSvg($name, true);
        if (!$src) {
            return '';
        }
        return "<img src='$src' class='svg-img' alt='svg image'>";
    }


    function getImg(string $name): string
    {
        return HelperPath::pathToUrl(Container::getLoader()->getPathImg() . $name);
    }
}
