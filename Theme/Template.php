<?php

namespace Vnet\Theme;

use Vnet\Helpers\ArrayHelper;
use Vnet\Helpers\Path;

class Template
{

    /**
     * @var string
     */
    private static $pathTemplates = THEME_PATH . 'template-parts';
    private static $pathSections = THEME_PATH . 'template-sections';

    /**
     * @var self[]
     */
    private static $arTemplates = [];

    /**
     * - Настройки кэширования для шаблонов
     * - ключ = названию шаблона, значение = секунды кэширования
     * - если установить -1 кэш будет действовать до его сброса
     * @var array
     */
    private static $cacheTemplates = [];

    /**
     * - Абсолютный путь к папке с кэшем
     * - Без данного свойства кэширование работать не будет
     * @var string
     */
    private static $cachePath = '';

    /**
     * @var self[]
     */
    private static $arSections = [];

    private $name = '';

    private $args = [];

    private $html = '';

    private $file = '';

    private $relativeFile = '';

    private $key = '';


    static function setCache(string $template, int $time): string
    {
        self::$cacheTemplates[$template] = $time;
        return self::class;
    }

    static function setCacheArray(array $cacheTemplates): string
    {
        self::$cacheTemplates = array_merge(self::$cacheTemplates, $cacheTemplates);
        return self::class;
    }

    static function setCachePath(string $path): string
    {
        self::$cachePath = $path;
        if (!file_exists(self::$cachePath)) {
            mkdir(self::$cachePath, 0777, true);
        }
        return self::class;
    }

    static function addResetButton(): string
    {
        return self::class;
    }

    /**
     * 
     * @param string $name относительный путь к файлу без расширения
     * @param mixed $args [optional]
     * 
     * @return self
     * 
     */
    static function getTemplate($name, $args = [])
    {
        $file = self::$pathTemplates . '/' . $name . '.php';

        if (!file_exists($file)) {
            self::consoleError("Template [$name] does not exists");
        }

        $key = md5($_SERVER['REQUEST_URI'] . $name . serialize($args));

        if (!isset(self::$arTemplates[$key])) {
            self::$arTemplates[$key] = new self($name, $key, $file, $args);
        }

        return self::$arTemplates[$key];
    }

    static function theTemplate($name, $args = [])
    {
        self::getTemplate($name, $args)->render();
    }


    /**
     * 
     * @param string $name относительный путь к файлу без расширения
     * @param mixed $args [optional]
     * 
     * @return self
     * 
     */
    static function getSection($name, $args = [], $cacheTime = 0)
    {
        if (is_callable($args)) {
            $args = call_user_func($args);
        }

        $key = md5($_SERVER['REQUEST_URI'] . $name . serialize($args));

        $file = self::$pathSections . '/' . $name . '.php';

        if (!file_exists($file)) {
            self::consoleError("Section [$name] does not exists");
        }

        if (isset(self::$arSections[$key])) {
            return self::$arSections[$key];
        }

        if (!isset(self::$arSections[$key])) {
            self::$arSections[$key] = new self($name, $key, $file, $args, $cacheTime);
        }

        return self::$arSections[$key];
    }


    static function theSection($name, $args = [], $cacheTime = 0)
    {
        self::getSection($name, $args, $cacheTime)->render();
    }


    /**
     * - Выводит ошибку в консоль броузера
     * @param string $msg
     */
    private static function consoleError($msg)
    {
        $trace = debug_backtrace();
        $trace = array_map(function ($val) {
            $file = str_replace($_SERVER['DOCUMENT_ROOT'], '', $val['file']);
            return [
                'file' => $file,
                'line' => $val['line'],
                'function' => $val['function']
            ];
        }, $trace);
        unset($trace[0]);
        $trace = array_values($trace);
        $trace = addslashes(json_encode($trace, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        echo "<script>console.error('$msg', '\\n', JSON.parse('$trace'));</script>";
    }


    private function __construct($name = '', $key = '', $file = '', $args = [], $cacheTime = 0)
    {
        $this->name = $name;
        $this->key = $key;
        $this->args = $args;
        $this->file = $file;
        $this->relativeFile = str_replace(THEME_PATH, '', $file);

        if (!$this->file) {
            $this->html = $this->name;
            $this->name = '_html_';
            return;
        }

        $this->html = $this->fetchHtml($cacheTime);
    }


    function __toString()
    {
        echo $this->html;
    }


    private function fetchHtml(int $cacheTime = 0): string
    {
        if (!file_exists($this->file)) {
            return '';
        }

        $str = '';

        if (is_user_logged_in()) {
            $str .= "\r\n<!-- [START_TEMPLATE name:{$this->name}; key:{$this->key}; file:{$this->relativeFile}] -->\r\n";
        }

        if ($cacheTime && !is_user_logged_in()) {
            $cachePath = Path::join(THEME_PATH, 'template-cache');
            if (!file_exists($cachePath)) {
                mkdir($cachePath, 0777);
                $content = $this->execTemplate();
            } else {
                $cacheFile = Path::join(THEME_PATH, 'template-cache', $this->key);
                $content = '';
                if (file_exists($cacheFile)) {
                    if ((time() - filemtime($cacheFile)) > $cacheTime) {
                        @unlink($cacheFile);
                        $content = $this->execTemplate();
                        file_put_contents($cacheFile, $content);
                    } else {
                        $content = file_get_contents($cacheFile);
                        $str .= "\r\n<!-- [FROM CACHE] -->\r\n";
                    }
                } else {
                    $content = $this->execTemplate();
                    file_put_contents($cacheFile, $content);
                }
            }
        } else {
            $content = $this->execTemplate();
        }

        $str .= $content;

        if (is_user_logged_in()) {
            $str .= "\r\n<!-- [END_TEMPLATE name:{$this->name}; key:{$this->key}; file:{$this->relativeFile}] -->\r\n";
        }

        return $str;
    }


    private function execTemplate()
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
        $res = ArrayHelper::get($this->args, $key, $def);
        if (!$checkEmpty) {
            return $res;
        }
        return $res ? $res : $def;
    }

    function getArgInt($key, $def = 0, $checkEmpty = false): int
    {
        return (int)$this->getArg($key, $def, $checkEmpty);
    }

    function getArgFloat($key, $def = 0, $checkEmpty = false): float
    {
        return (float)$this->getArg($key, $def, $checkEmpty);
    }


    function getName()
    {
        return $this->name;
    }


    function render()
    {
        echo $this->html;
    }
}
