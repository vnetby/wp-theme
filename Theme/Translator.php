<?php

namespace Vnet\Theme;

class Translator
{

    const LOCALE_RU = 'ru_RU';
    const LOCALE_PL = 'pl_PL';

    const DEF_LOCALE = self::LOCALE_RU;


    static function isRu(): bool
    {
        return self::getLocale() === self::LOCALE_RU;
    }


    static function isPl(): bool
    {
        return self::getLocale() === self::LOCALE_PL;
    }


    static function getLocale(): string
    {
        return defined('SITE_LOCALE') ? constant('SITE_LOCALE') : self::DEF_LOCALE;
    }
}
