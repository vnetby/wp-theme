<?php

/**
 * @see https://wp-kama.ru/function/register_taxonomy
 */

namespace Vnetby\Wptheme\Entities\Admin;

use Error;
use Vnetby\Helpers\HelperArr;
use Vnetby\Helpers\HelperFn;

/**
 * @template TParamLabels of array{
 *      name: ?string,
 *      singular_name: ?string,
 *      menu_name: ?string,
 *      search_items: ?string,
 *      popular_items: ?string,
 *      all_items: ?string,
 *      parent_item: ?string,
 *      parent_item_colon: ?string,
 *      edit_item: ?string,
 *      update_item: ?string,
 *      add_new_item: ?string,
 *      view_item: ?string,
 *      new_item_name: ?string,
 *      separate_items_with_commas: ?string,
 *      add_or_remove_items: ?string,
 *      choose_from_most_used: ?string,
 *      popular_items: ?string,
 *      not_found: ?string,
 *      back_to_items: ?string
 * }
 * 
 * // setters
 * // every method is an underscore property of \WP_Taxonomy object without "set" prefix
 * 
 * @method static setLabel(string $val)
 * @method static setDescription(string $val)
 * @method static setLabels(TParamLabels $val)
 * @method static setPublic(bool $val)
 * @method static setShowUi(bool $val)
 * @method static setShowInNavMenus(bool $val)
 * @method static showTagcloud(bool $val)
 * @method static setShowInRest(bool $val)
 * @method static setRestBase(string $val)
 * @method static setRestControllerClass(string $val)
 * @method static setRestNamespace(string $val)
 * @method static setHierarchical(bool $val)
 * @method static setUpdateCountCallback(string $val)
 * @method static setRewrite(array|false $val)
 * @method static setPubliclyQueryable(bool $val)
 * @method static setQueryVar(string|bool $val)
 * @method static setCapabilities(string[] $val)
 * @method static setMetaBoxCb(string $val)
 * @method static setMetaBoxSanitizeCb(callable $val)
 * @method static setShowAdminColumn(bool $val)
 * @method static setShowInQuickEdit(bool $val)
 * @method static setSort(bool $val)
 * @method static setDefaultTerm(string|array $val)
 * 
 * // getters (available only after init action)
 * // every method is an underscore property of \WP_Taxonomy object without "get" prefix
 * 
 * @method string getLabel()
 * @method string getDescription()
 * @method TParamLabels getLabels()
 * @method bool getPublic()
 * @method bool getShowUi()
 * @method bool getShowInNavMenus()
 * @method bool showTagcloud()
 * @method bool getShowInRest()
 * @method string getRestBase()
 * @method string getRestControllerClass()
 * @method string getRestNamespace()
 * @method bool getHierarchical()
 * @method string getUpdateCountCallback()
 * @method array|false getRewrite()
 * @method bool getPubliclyQueryable()
 * @method string|bool getQueryVar()
 * @method string[] getCapabilities()
 * @method string getMetaBoxCb()
 * @method callable getMetaBoxSanitizeCb()
 * @method bool getShowAdminColumn()
 * @method bool getShowInQuickEdit()
 * @method bool getSort()
 * @method string|array getDefaultTerm()
 */
class AdminTaxonomy extends Admin
{

    /**
     * @var \WP_Taxonomy
     */
    protected $wpItem;


    /**
     * - Регистрирует таксономию в WP
     * @return static
     */
    function register(array $params = [])
    {
        $this->params = array_merge($this->params, $params);
        // это встроенная таксономия
        if (!$this->params) {
            if ($wpItem = get_taxonomy($this->getKey())) {
                $this->wpItem = $wpItem;
            } else {
                throw new Error("Taxonomy {$this->getKey()} is not registered");
            }
            return;
        }

        $this->filterParams();

        // add_action('init', function () {
        $wpItem = register_taxonomy($this->getKey(), null, $this->params);

        if (is_wp_error($wpItem)) {
            throw new Error(implode(PHP_EOL, $wpItem->get_error_messages()));
        }

        $this->wpItem = $wpItem;
        // });

        return $this;
    }


    function filterParams()
    {
        $params = $this->params;

        $defLabels = [
            'name' => __('Элементы', 'vnet'),
            'singular_name' => __('Элемент', 'vnet'),
            'search_items' => __('Найти', 'vnet'),
            'all_items' => __('Все элементы', 'vnet'),
            'view_item ' => __('Открыть элемент', 'vnet'),
            'parent_item' => __('Родительский элемент', 'vnet'),
            'parent_item_colon' => __('Родительский элемент:', 'vnet'),
            'edit_item' => __('Редактировать', 'vnet'),
            'update_item' => __('Обновить', 'vnet'),
            'add_new_item' => __('Добавить новый элемент', 'vnet'),
            'new_item_name' => __('Новое название', 'vnet'),
            'menu_name' => __('Элементы', 'vnet'),
            'back_to_items' => __('← Назад', 'vnet'),
        ];

        $labels = $params['labels'] ?? [];

        if (empty($params['label'])) {
            $params['label'] = __('Элементы', 'vnet');
        }

        if (empty($labels['name'])) {
            $labels['name'] = $params['label'];
        }

        if (empty($labels['menu_name'])) {
            $labels['menu_name'] = $params['label'];
        }

        $labels = array_merge($defLabels, $labels);

        $params['labels'] = $labels;

        $this->params = $params;
    }


    /**
     * - Добавляет колонку на странице списка терминов
     * @param string $colKey уникальный ключ новой колонки
     * @param string $colLabel Название колонки
     * @param callable $getValCallback функция для получения значения ячейки
     * @param string $position либо ключ колонки после которой вставить текущую, либо first|last
     */
    function addColumn(string $colKey, string $colLabel, callable $getValCallback, string $position = 'last')
    {
        $tax = $this->getKey();
        add_filter("manage_edit-{$tax}_columns", function (array $columns) use ($colKey, $position, $colLabel) {
            if ($position === 'last') {
                $position = array_keys($columns)[count(array_keys($columns)) - 1];
            } else if ($position === 'first') {
                $position = 'cb';
            }
            $columns = HelperArr::insert($columns, $position, [$colKey => $colLabel]);
            return $columns;
        });
        add_filter("manage_{$tax}_custom_column", function ($val, string $colName, int $termId) use ($colKey, $getValCallback) {
            if ($colName === $colKey) {
                return call_user_func($getValCallback, $termId);
            }
            return $val;
        }, 10, 3);
    }


    function removeColumn(string $colKey)
    {
        add_filter("manage_edit-{$this->getKey()}_columns", function (array $columns) use ($colKey) {
            if (isset($columns[$colKey])) {
                unset($columns[$colKey]);
            }
            return $columns;
        });
    }


    function hidePosts()
    {
        $this->removeColumn('posts');
    }


    /**
     * - Скрывает поле с описанием на странице таксономии
     */
    function hideDescription()
    {
        add_filter("manage_edit-{$this->getKey()}_columns", function (array $columns) {
            if (isset($columns['description'])) {
                unset($columns['description']);
            }
            return $columns;
        });
        $this->theAdminStyle("
            body.taxonomy-{$this->getKey()} .term-description-wrap {
                display: none!important;
            }
        ");
    }


    /**
     * - Скрывает поле с slug на странице таксономии
     * @return void 
     */
    function hideSlug()
    {
        add_filter("manage_edit-{$this->getKey()}_columns", function (array $columns) {
            if (isset($columns['slug'])) {
                unset($columns['slug']);
            }
            return $columns;
        });
        $this->theAdminStyle("
            body.taxonomy-{$this->getKey()} .term-slug-wrap,
            body.taxonomy-{$this->getKey()} .inline-edit-col label:last-child {
                display: none!important;
            }
        ");
    }


    /**
     * - Скрывает все что связано с плагином yast seo на странице таксономии
     */
    function hideYoast()
    {
        add_filter("manage_edit-{$this->getKey()}_columns", function (array $columns) {
            if (isset($columns['wpseo-score'])) {
                unset($columns['wpseo-score']);
            }
            if (isset($columns['wpseo-score-readability'])) {
                unset($columns['wpseo-score-readability']);
            }
            return $columns;
        });

        $this->theAdminStyle("
            body.taxonomy-{$this->getKey()} #wpseo_meta {
                display: none!important;
            }
        ");
    }


    function isAdminPage(): bool
    {
        if (empty($_GET['post_type']) || empty($_GET['taxonomy'])) {
            return false;
        }
        return $_GET['taxonomy'] === $this->getKey();
    }


    /**
     * - Делает страницу списка на всю ширину
     */
    function fullPage()
    {
        if (!$this->isAdminPage()) {
            return;
        }

        add_filter('admin_body_class', function ($classes) {
            if (isset($_GET['add-new'])) {
                $classes .= ' add-new';
            }
            return $classes;
        });

        $this->theAdminStyle("
            body.taxonomy-{$this->getKey()} #col-left,
            body.taxonomy-{$this->getKey()} #col-right {
                float: none!important;
                width: 100%!important;
            }
            body.taxonomy-{$this->getKey()}:not(.add-new) #col-left {
                display: none!important;
            }
            body.taxonomy-{$this->getKey()}.add-new #col-right,
            body.taxonomy-{$this->getKey()}.add-new .page-title-action,
            body.taxonomy-{$this->getKey()}.add-new .search-box,
            body.taxonomy-{$this->getKey()}.add-new #screen-meta-links {
                display: none!important;
            }
        ");

        $this->theAdminScript("
            jQuery('body.taxonomy-{$this->getKey()} .wp-heading-inline')
                .after('<a href=\"edit-tags.php?taxonomy={$this->getKey()}&post_type={$_GET['post_type']}&add-new=1\" class=\"page-title-action\">Добавить</a>');
        ");
    }


    function onAfterUpdate(callable $callback)
    {
        add_action('saved_' . $this->getKey(), function ($termId, $termTaxId, $update, $args) use ($callback) {
            HelperFn::execCallback($callback, $termId, $termTaxId, $update, $args);
        }, 10, 4);
    }
}
