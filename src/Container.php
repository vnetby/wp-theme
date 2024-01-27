<?php

namespace Vnetby\Wptheme;

use Vnetby\Wptheme\Ajax\Response;
use Vnetby\Wptheme\Entities\EntityCategory;
use Vnetby\Wptheme\Entities\EntityPage;
use Vnetby\Wptheme\Entities\EntityPost;
use Vnetby\Wptheme\Entities\EntityTag;
use Vnetby\Wptheme\Front\Template;
use Vnetby\Wptheme\Seo\Seo;
use Vnetby\Wptheme\Seo\SeoArchive;
use Vnetby\Wptheme\Seo\SeoOptions;
use Vnetby\Wptheme\Seo\SeoPost;
use Vnetby\Wptheme\Seo\SeoTerm;


/**
 * @method static void setClassLoader(class-string<Loader> $className)
 * @method static class-string<Loader> getClassLoader()
 * 
 * @method static void setClassValidator(class-string<Validator> $className)
 * @method static class-string<Validator> getClassValidator()
 * 
 * @method static void setClassAjaxResponse(class-string<Response> $className)
 * @method static class-string<Response> getClassAjaxResponse()
 * 
 * @method static void setClassTemplate(class-string<Template> $className)
 * @method static class-string<Template> getClassTemplate()
 * 
 * @method static void setClassMailer(class-string<Mailer> $className)
 * @method static class-string<Mailer> getClassMailer()
 * 
 * @method static void setClassEntityPage(class-string<EntityPage> $className)
 * @method static class-string<EntityPage> getClassEntityPage()
 * 
 * @method static void setClassEntityPost(class-string<EntityPost> $className)
 * @method static class-string<EntityPost> getClassEntityPost()
 * 
 * @method static void setClassEntityTag(class-string<EntityTag> $className)
 * @method static class-string<EntityTag> getClassEntityTag()
 * 
 * @method static void setClassEntityCategory(class-string<EntityCategory> $className)
 * @method static class-string<EntityCategory> getClassEntityCategory()
 * 
 * @method static void setClassSeo(class-string<Seo> $className)
 * @method static class-string<Seo> getClassSeo()
 * 
 * @method static void setClassSeoTerm(class-string<SeoTerm> $className)
 * @method static class-string<SeoTerm> getClassSeoTerm()
 * 
 * @method static void setClassSeoPost(class-string<SeoPost> $className)
 * @method static class-string<SeoPost> getClassSeoPost()
 * 
 * @method static void setClassSeoArchive(class-string<SeoArchive> $className)
 * @method static class-string<SeoArchive> getClassSeoArchive()
 * 
 * @method static void setClassSeoOptions(class-string<SeoOptions> $className)
 * @method static class-string<SeoOptions> getClassSeoOptions()
 */
class Container
{
    private static string $classLoader = Loader::class;
    private static string $classValidator = Validator::class;
    private static string $classAjaxResponse = Response::class;
    private static string $classTemplate = Template::class;
    private static string $classMailer = Mailer::class;

    private static string $classEntityPage = EntityPage::class;
    private static string $classEntityPost = EntityPost::class;
    private static string $classEntityTag = EntityTag::class;
    private static string $classEntityCategory = EntityCategory::class;

    private static string $classSeo = Seo::class;
    private static string $classSeoTerm = SeoTerm::class;
    private static string $classSeoPost = SeoPost::class;
    private static string $classSeoArchive = SeoArchive::class;
    private static string $classSeoOptions = SeoOptions::class;


    public static function __callStatic($name, $arguments)
    {
        if (preg_match("/^getClass/", $name)) {
            $prop = lcfirst(preg_replace("/^get/", '', $name));
            if (isset(self::$$prop)) {
                return self::$$prop;
            }
            return '';
        }

        if (preg_match("/^setClass/", $name)) {
            $prop = lcfirst(preg_replace("/^set/", '', $name));
            if (isset(self::$$prop)) {
                self::$$prop = $arguments[0];
            }
            return null;
        }
    }

    /**
     * @return Loader
     */
    static function getLoader()
    {
        return self::getClassLoader()::getInstance();
    }

    /**
     * @return Seo
     */
    static function getSeo()
    {
        return self::getClassSeo()::getInstance();
    }
}
