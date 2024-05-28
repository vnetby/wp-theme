<?php

namespace Vnetby\Wptheme\Traits\Config;

use Vnetby\Helpers\HelperArr;
use Vnetby\Helpers\HelperPath;

trait ConfigLocale
{
    protected string $timeZone = 'UTC';

    protected string $locale = 'en_EN';

    protected string $dateFormat = 'Y-m-d';

    protected string $timeFormat = 'H:i:s';

    protected string $textDomain = 'vnet';

    /**
     * - Массив строк с сообщениями
     * @var array<string,string>
     */
    protected array $messages = [];


    /**
     * - Регистрирует локаль, устанавливает необходимые хуки для корректной работы
     * @return static
     */
    protected function registerLocale()
    {
        // устанавливаем переводы
        load_textdomain($this->textDomain, $this->themePath('languages', $this->getLocale() . '.mo'));
        add_action('after_setup_theme', function () {
            load_theme_textdomain($this->textDomain, $this->themePath('languages'));
        });

        // устанавливаем локализацию сайта
        if (!is_admin()) {
            switch_to_locale($this->getLocale());
            add_filter('locale', function ($locale) {
                return $this->getLocale();
            });
            add_filter('language_attributes', function ($value) {
                $locale = $this->getLocale();
                $attr = str_replace("_", "-", $locale);
                return 'lang="' . $attr . '"';
            });
        }
        return $this;
    }


    /**
     * @param string $locale
     * @return static
     */
    function setLocale(string $locale)
    {
        $this->locale = $locale;
        $this->registerLocale();
        return $this;
    }

    function getLocale(): string
    {
        return $this->locale;
    }

    function setDateFormat(string $format)
    {
        $this->dateFormat = $format;
        return $this;
    }

    function getDateFormat(): string
    {
        return $this->dateFormat;
    }

    function setTimeFormat(string $format)
    {
        $this->timeFormat = $format;
        return $this;
    }

    function getTimeFormat(): string
    {
        return $this->timeFormat;
    }

    function getDateTimeFormat(): string
    {
        return $this->getDateFormat() . ' ' . $this->getTimeFormat();
    }

    /**
     * @param array $messages
     * @return static
     */
    function setMessages(array $messages)
    {
        $this->messages = $messages;
        return $this;
    }

    /**
     * @return static
     */
    function addMessage(string $key, string $message)
    {
        $this->messages[$key] = $message;
        return $this;
    }

    /**
     * @return static
     */
    function addMessages(array $messages)
    {
        foreach ($messages as $key => $msg) {
            $this->addMessage($key, $msg);
        }
        return $this;
    }


    /**
     * - Получает текстовое сообщение из массива self::$messages
     * - вернет $key если не найдено
     * @param string $key
     * @param array $replace [optional] массив значений для замены в строке сообщения
     * @return string|string[]
     */
    function getMessage($key, $replace = [])
    {
        $msg = HelperArr::get($this->messages, $key, $key);

        if (!$replace || is_array($msg)) {
            return $msg;
        }

        foreach ($replace as $i => $str) {
            $n = $i + 1;
            $msg = str_replace("\$$n", $str, $msg);
        }

        return $msg;
    }

    /**
     * @param string $zone
     * @return static
     */
    function setTimeZone(string $zone)
    {
        $this->timeZone = $zone;
        return $this;
    }

    function getTimezone(): string
    {
        return $this->timeZone;
    }
}
