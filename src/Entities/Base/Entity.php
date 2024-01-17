<?php

namespace Vnetby\Wptheme\Entities\Base;

use Closure;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use Vnetby\Wptheme\Container;
use Vnetby\Wptheme\Entities\Admin\Admin;
use Vnetby\Wptheme\Traits\ModelSeo;

abstract class Entity
{
    const CLASS_ADMIN = '';
    const KEY = '';
    const TTL_CACHE = 0;

    /**
     * @var Admin
     */
    private static $adminInstances = [];

    protected $wpItem;

    protected function __construct($wpItem)
    {
        $this->wpItem = $wpItem;
    }

    /**
     * - Инициализирует сущность в wp
     * @param Admin $admin
     */
    static function setup($admin)
    {
        $admin->register();
    }

    /**
     * - Выполняет фильтрацию по элементам
     * @return DbResult<static>
     */
    abstract static function filter(array $filter = [], int $page = 1, int $perPage = -1);

    /**
     * @return Admin
     */
    static function getAdmin()
    {
        $class = get_called_class();
        if (!isset(self::$adminInstances[$class])) {
            $adminClass = static::CLASS_ADMIN;
            self::$adminInstances[$class] = new $adminClass($class);
        }
        return self::$adminInstances[$class];
    }

    static function isEditPage(): bool
    {
        if (static::isTaxonomy()) {
            if (empty($_GET['post_type']) || empty($_GET['taxonomy'])) {
                return false;
            }
            return is_admin() && $_GET['taxonomy'] === static::getKey();
        }
        if (static::isPostType()) {
            return is_admin() && !empty($_GET['post_type']) && $_GET['post_type'] === static::getKey();
        }
        return false;
    }

    /**
     * - Получает текущий элемент
     * @return ?static
     */
    static function getCurrent()
    {
        if (static::isPostType()) {
            if ($post = get_post()) {
                return static::getByWpItem($post);
            }
            return null;
        }
        if (static::isTaxonomy()) {
            $obj = get_queried_object();
            if ($obj && $obj instanceof \WP_Term) {
                return static::getByWpItem($obj);
            }
            return null;
        }
        return null;
    }

    /**
     * - Получает элемент по ID
     * @param integer $id
     * @return ?static
     */
    static function getById(int $id)
    {
        return static::fetchCache(function () use ($id) {
            if (static::isPostType()) {
                $post = get_post($id);
                return $post && !is_wp_error($post) ? static::getByWpItem($post) : null;
            }
            if (static::isTaxonomy()) {
                $term = get_term_by('id', $id, static::getKey());
                return $term && !is_wp_error($term) ? static::getByWpItem($term) : null;
            }
            return null;
        });
    }

    /**
     * - Получает по объекту элемента wordpress
     * @param \WP_Post|\WP_Term $wpItem
     * @return static
     */
    static function getByWpItem($wpItem)
    {
        return static::fetchCache(function () use ($wpItem) {
            return new static($wpItem);
        });
    }

    static function getArchiveSeoTitle(): string
    {
        if (static::isPostType()) {
            return Container::getClassSeo()::getArchiveTitle(static::getKey());
        }
        return '';
    }

    static function getArchiveSeoDesc(): string
    {
        if (static::isPostType()) {
            return Container::getClassSeo()::getArchiveDesc(static::getKey());
        }
        return '';
    }

    static function getArchiveSeoImageId(): int
    {
        if (static::isPostType()) {
            return Container::getClassSeo()::getArchiveImageId(static::getKey());
        }
        return 0;
    }

    static function getArchiveSeoImage(): string
    {
        if (static::isPostType()) {
            return Container::getClassSeo()::getArchiveImage(static::getKey());
        }
        return '';
    }

    protected static function getWpDb(): \wpdb
    {
        return $GLOBALS['wpdb'];
    }

    /**
     * - Проверяет является ли данная сущность, сущностью типа поста
     * @return boolean
     */
    static function isPostType(): bool
    {
        $class = get_called_class();
        return $class instanceof EntityPostType || is_subclass_of($class, EntityPostType::class);
    }

    /**
     * - Проверяет является ли данныя сущность, сущностью таксономии
     * @return boolean
     */
    static function isTaxonomy(): bool
    {
        $class = get_called_class();
        return $class instanceof EntityTaxonomy || is_subclass_of($class, EntityTaxonomy::class);
    }

    /**
     * - Получает уникальный ключ сущности
     * @return string
     */
    static function getKey(): string
    {
        return static::KEY;
    }

    /**
     * - Явлаяется ли страница архивом текущего типа поста или таксономии
     * @return boolean
     */
    static function isArchive(): bool
    {
        if (static::isPostType()) {
            return is_post_type_archive(static::getKey());
        }
        return is_tax(static::getKey());
    }



    /**
     * - Устанавливает значение поля
     * @param string $key ключ из WP_Post
     * @param mixed $value
     * 
     * @return static
     */
    function setField(string $key, $value)
    {
        $this->wpItem->$key = $value;
        return $this;
    }


    /**
     * - Устанавливает значение в зависимости от того является ли элемент постом или термином
     *
     * @param string $postField
     * @param string $termField
     * @param mixed $value
     * 
     * @return static
     */
    private function setRightField(string $postField, string $termField, $value)
    {
        return $this->setField(static::isPostType() ? $postField : $termField, $value);
    }


    function getField(string $key, $def = null)
    {
        return $this->wpItem->$key ?? $def;
    }


    private function getRightField(string $postField, string $termField, $def = null)
    {
        return $this->getField(static::isPostType() ? $postField : $termField, $def);
    }


    function getId(): int
    {
        return (int)$this->getRightField('ID', 'term_id', 0);
    }


    function getTitle(): string
    {
        return $this->getRightField('post_title', 'name', '');
    }


    function getSlug(): string
    {
        return $this->getRightField('post_name', 'slug', '');
    }


    function getParentId(): int
    {
        return (int)$this->getRightField('post_parent', 'parent', 0);
    }


    function getUrl(): string
    {
        return static::isPostType() ? get_permalink($this->wpItem) : get_term_link($this->wpItem, $this->wpItem->taxonomy);
    }


    function getEditUrl(): string
    {
        return static::isPostType() ? get_edit_post_link($this->wpItem) : get_edit_term_link($this->wpItem);
    }


    function getContent(): string
    {
        return $this->getRightField('post_content', 'description', '');
    }


    function getMeta(string $key, bool $single = true)
    {
        return static::isPostType() ? get_post_meta($this->getId(), $key, $single) : get_term_meta($this->getId(), $key, $single);
    }


    function getAcfField(string $selector, $def = null)
    {
        if (!function_exists('get_field')) {
            return $def;
        }
        return get_field($selector, $this->wpItem, true);
    }


    /**
     * @return \WP_Post|\WP_Term
     */
    function getWpItem()
    {
        return $this->wpItem;
    }


    function getOrder(): int
    {
        if (static::isTaxonomy()) {
            $val = get_term_meta($this->getId(), 'tax_position', true);
            return $val ? (int)$val : 0;
        }
        return (int)$this->wpItem->menu_order;
    }


    function updateMeta(string $key, $value, $prevValue = '')
    {
        if (static::isPostType()) {
            return update_post_meta($this->getId(), $key, $value, $prevValue);
        }
        return update_term_meta($this->getId(), $key, $value, $prevValue);
    }


    function deleteMeta(string $key, $value = '')
    {
        if (static::isPostType()) {
            return delete_post_meta($this->getId(), $key, $value);
        }
        return delete_term_meta($this->getId(), $key, $value);
    }

    /**
     * - Является ли текущая страница детальной страницей элемента
     * @return boolean
     */
    static function isSingle(): bool
    {
        return is_singular(static::getKey());
    }

    function getSeoTitle(): string
    {
        if (static::isPostType()) {
            return Container::getClassSeo()::getPostTitle($this->getId());
        }
        return Container::getClassSeo()::getTermTitle($this->getId());
    }

    function getSeoDesc(): string
    {
        if (static::isPostType()) {
            return Container::getClassSeo()::getPostDesc($this->getId());
        }
        return Container::getClassSeo()::getTermDesc($this->getId());
    }

    function getSeoImageId(): int
    {
        if (static::isPostType()) {
            return Container::getClassSeo()::getPostImageId($this->getId());
        }
        return Container::getClassSeo()::getTermImageId($this->getId());
    }

    function getSeoImage(): string
    {
        if (static::isPostType()) {
            return Container::getClassSeo()::getPostImage($this->getId());
        }
        return Container::getClassSeo()::getTermImage($this->getId());
    }

    function getSeoSchemaType(): \Vnetby\Schemaorg\Types\Type
    {
        if (static::isPostType()) {
            return Container::getClassSeo()::getPostSchemaType($this->getId());
        }
        return Container::getClassSeo()::getTermSchemaType($this->getId());
    }

    function getCanonicalUrl(): string
    {
        return '';
    }


    /**
     * - Получает данные из кэша если они есть, в противном случае вызывает переданную функцию
     * - Уникальный ключ кэша формируется автоматичкски из следующих частей:
     *      - класс в котором вызван метод (static::class)
     *      - название метода, в котром вызван данный метод
     *      - $keySuffix, или хэш md5 серилизированных аргументов метода, в котором вызван данный метод
     *      - ID элемента, если данный метод был вызван в контесте объекта сущности
     * - В качестве разделителя частей ключа кэша используется знак :
     * - Объектное кэширование отработает вне зависимости от параметра $ttl
     * - Если в качестве аргументов метода, в котором вызван данный метод,
     *      переданы параметры которые нельзя серилизовать, например SimpleXMLElement,
     *      либо переданные аргументы являются объектами/массивами большщой вложенности,
     *      необходимо вручную передать $keySuffix
     * @param callable $fn - функция которая отработает в случае если кэша нет
     * @param string $keySuffix - доподнительная часть ключа кэша
     *      если не передать, в качестве такого ключа, будут использованы
     *      аргументы метода, в контекстве которого был вызван данный метод
     * @param integer|null $ttl время жизни кэша, по умолчанию static::TTL_CACHE
     */
    protected static function fetchCache(callable $fn, $keySuffix = '', ?int $ttl = null)
    {
        $debug = debug_backtrace();

        $callFrom = $debug[1];

        if (!$keySuffix) {
            $args = $callFrom['args'] ?? [];
            if ($args) {
                $keySuffix = md5(serialize($args));
            }
        }

        $class = get_called_class();

        $fullKey = $class . ':' . $callFrom['function'];

        if ($keySuffix) {
            $fullKey .= ':' . $keySuffix;
        }

        if ($callFrom['type'] == '->' && isset($callFrom['object']) && $callFrom['object'] instanceof self) {
            $fullKey .= ':' . $callFrom['object']->getId();
        }

        if ($ttl === null) {
            $ttl = static::TTL_CACHE;
        }

        return Container::getLoader()->fetchCache($fullKey, $fn, $ttl);
    }
}
