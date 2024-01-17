<?php

namespace Vnetby\Wptheme;

use Vnetby\Wptheme\Ajax\Response;
use Vnetby\Wptheme\Front\Template;
use Vnetby\Wptheme\Models\ModelPostType;
use Vnetby\Wptheme\Models\ModelTaxonomy;

class Container
{
    private static string $classLoader = Loader::class;
    private static string $classValidator = Validator::class;
    private static string $classAjaxResponse = Response::class;
    private static string $classTemplate = Template::class;
    private static string $classMailer = Mailer::class;
    private static string $classSeo = Seo::class;


    static function getLoader(): Loader
    {
        return self::$classLoader::getInstance();
    }

    /**
     * @param class-string<Loader> $classLoader
     */
    static function setClassLoader(string $classLoader)
    {
        self::$classLoader = $classLoader;
    }

    /**
     * @param class-string<Validator> $classValidator
     */
    static function setClassValidator(string $classValidator)
    {
        self::$classValidator = $classValidator;
    }

    /**
     * @return class-string<Validator>
     */
    static function getClassValidator(): string
    {
        return self::$classValidator;
    }

    /**
     * @param class-string<Response> $classAjaxResponse
     */
    static function setClassAjaxResponse(string $classAjaxResponse)
    {
        self::$classAjaxResponse = $classAjaxResponse;
    }

    /**
     * @return class-string<Response>
     */
    static function getClassAjaxResponse(): string
    {
        return self::$classAjaxResponse;
    }

    /**
     * @param class-string<Template> $classTemplate
     */
    static function setClassTemplate(string $classTemplate)
    {
        self::$classTemplate = $classTemplate;
    }

    /**
     * @return class-string<Template>
     */
    static function getClassTemplate(): string
    {
        return self::$classTemplate;
    }

    /**
     * @param class-string<Mailer> $classMailer
     */
    static function setClassMailer(string $classMailer)
    {
        self::$classMailer = $classMailer;
    }

    /**
     * @return class-string<Mailer>
     */
    static function getClassMailer(): string
    {
        return self::$classMailer;
    }

    /**
     * @param class-string<Seo> $classSeo
     */
    static function setClassSeo(string $classSeo)
    {
        self::$classSeo = $classSeo;
    }

    /**
     * @return class-string<Seo>
     */
    static function getClassSeo(): string
    {
        return self::$classSeo;
    }
}
