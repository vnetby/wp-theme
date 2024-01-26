<?php

namespace Vnetby\Wptheme;

use Vnetby\Wptheme\Ajax\Response;
use Vnetby\Wptheme\Front\Template;
use Vnetby\Wptheme\Seo\Seo;
use Vnetby\Wptheme\Seo\SeoArchive;
use Vnetby\Wptheme\Seo\SeoOptions;
use Vnetby\Wptheme\Seo\SeoPost;
use Vnetby\Wptheme\Seo\SeoTerm;

class Container
{
    private static string $classLoader = Loader::class;
    private static string $classValidator = Validator::class;
    private static string $classAjaxResponse = Response::class;
    private static string $classTemplate = Template::class;
    private static string $classMailer = Mailer::class;

    private static string $classSeo = Seo::class;
    private static string $classSeoTerm = SeoTerm::class;
    private static string $classSeoPost = SeoPost::class;
    private static string $classSeoArchive = SeoArchive::class;
    private static string $classSeoOptions = SeoOptions::class;


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

    /**
     * @param class-string<SeoTerm> $classSeoTerm
     */
    static function setClassSeoTerm(string $classSeoTerm)
    {
        self::$classSeoTerm = $classSeoTerm;
    }

    /**
     * @return class-string<SeoTerm>
     */
    static function getClassSeoTerm(): string
    {
        return self::$classSeoTerm;
    }

    /**
     * @param class-string<SeoOptions> $classSeoOptions
     */
    static function setClassSeoOptions(string $classSeoOptions)
    {
        self::$classSeoOptions = $classSeoOptions;
    }

    /**
     * @return class-string<SeoOptions>
     */
    static function getClassSeoOptions(): string
    {
        return self::$classSeoOptions;
    }

    /**
     * @param class-string<SeoPost> $classSeoPost
     */
    static function setClassSeoPost(string $classSeoPost)
    {
        self::$classSeoPost = $classSeoPost;
    }

    /**
     * @return class-string<SeoPost>
     */
    static function getClassSeoPost(): string
    {
        return self::$classSeoPost;
    }

    /**
     * @param class-string<SeoArchive> $classSeoArchive
     */
    static function setClassSeoArchive(string $classSeoArchive)
    {
        self::$classSeoArchive = $classSeoArchive;
    }

    /**
     * @return class-string<SeoPost>
     */
    static function getClassSeoArchive(): string
    {
        return self::$classSeoArchive;
    }

    /**
     * @return Seo
     */
    static function getSeo()
    {
        return self::getClassSeo()::getInstance();
    }
}
