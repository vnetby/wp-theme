<?php

namespace Vnet\Types;

use Vnet\Helpers\ArrayHelper;
use Vnet\Helpers\HelperFn;
use Vnet\Traits\Instance;

class Taxonomy extends Core
{

    /**
     * @var null|\WP_Taxonomy
     */
    protected $taxonomy = null;


    protected function register(array $params, array $posts = [])
    {
        add_action('init', function () use ($params, $posts) {
            $taxonomy = register_taxonomy($this->slug, $posts, $this->filterParams($params));
            if (!is_wp_error($taxonomy)) {
                $this->taxonomy = $taxonomy;
            }
        });
    }


    private function filterParams(array $params): array
    {
        $defLabels = [
            'name' => 'Элементы',
            'singular_name' => 'Эоемент',
            'search_items' => 'Найти',
            'all_items' => 'Все элементы',
            'view_item ' => 'Открыть элемент',
            'parent_item' => 'Родительский элемент',
            'parent_item_colon' => 'Родительский элемент:',
            'edit_item' => 'Редактировать',
            'update_item' => 'Обновить',
            'add_new_item' => 'Добавить новый элемент',
            'new_item_name' => 'Новое название',
            'menu_name' => 'Элементы',
            'back_to_items' => '← Назад',
        ];

        $labels = $params['labels'] ?? [];

        if (empty($params['label'])) {
            $params['label'] = 'Элементы';
        }

        if (empty($labels['name'])) {
            $labels['name'] = $params['label'];
        }

        if (empty($labels['menu_name'])) {
            $labels['menu_name'] = $params['label'];
        }

        $labels = array_merge($defLabels, $labels);

        $params['labels'] = $labels;

        return $params;
    }


    /**
     * - Добавляет колонку на странице списка терминов
     * @param string $colKey уникальный ключ новой колонки
     * @param string $colLabel Название колонки
     * @param callable $getValCallback функция для получения значения ячейки
     * @param string $position либо ключ колонки после которой вставить текущую, либо first|last
     */
    protected function addColumn(string $colKey, string $colLabel, callable $getValCallback, string $position = 'last')
    {
        $tax = $this->slug;
        add_filter("manage_edit-{$tax}_columns", function (array $columns) use ($colKey, $position, $colLabel) {
            if ($position === 'last') {
                $position = array_keys($columns)[count(array_keys($columns)) - 1];
            } else if ($position === 'first') {
                $position = 'cb';
            }
            $columns = ArrayHelper::insert($columns, $position, [$colKey => $colLabel]);
            return $columns;
        });
        add_filter("manage_{$tax}_custom_column", function ($val, string $colName, int $termId) use ($colKey, $getValCallback) {
            if ($colName === $colKey) {
                return call_user_func($getValCallback, $termId);
            }
            return $val;
        }, 10, 3);
    }


    protected function removeColumn(string $colKey)
    {
        add_filter("manage_edit-{$this->slug}_columns", function (array $columns) use ($colKey) {
            if (isset($columns[$colKey])) {
                unset($columns[$colKey]);
            }
            return $columns;
        });
    }


    protected function hidePosts()
    {
        $this->removeColumn('posts');
    }


    /**
     * - Скрывает поле с описанием на странице таксономии
     */
    protected function hideDescription()
    {
        add_filter("manage_edit-{$this->slug}_columns", function (array $columns) {
            if (isset($columns['description'])) {
                unset($columns['description']);
            }
            return $columns;
        });
        $this->theAdminStyle("
            body.taxonomy-{$this->slug} .term-description-wrap {
                display: none!important;
            }
        ");
    }


    /**
     * - Скрывает поле с slug на странице таксономии
     * @return void 
     */
    protected function hideSlug()
    {
        add_filter("manage_edit-{$this->slug}_columns", function (array $columns) {
            if (isset($columns['slug'])) {
                unset($columns['slug']);
            }
            return $columns;
        });
        $this->theAdminStyle("
            body.taxonomy-{$this->slug} .term-slug-wrap,
            body.taxonomy-{$this->slug} .inline-edit-col label:last-child {
                display: none!important;
            }
        ");
    }


    /**
     * - Скрывает все что связано с плагином yast seo на странице таксономии
     */
    protected function hideYoast()
    {
        add_filter("manage_edit-{$this->slug}_columns", function (array $columns) {
            if (isset($columns['wpseo-score'])) {
                unset($columns['wpseo-score']);
            }
            if (isset($columns['wpseo-score-readability'])) {
                unset($columns['wpseo-score-readability']);
            }
            return $columns;
        });

        $this->theAdminStyle("
            body.taxonomy-{$this->slug} #wpseo_meta {
                display: none!important;
            }
        ");
    }


    function isAdminPage(): bool
    {
        if (empty($_GET['post_type']) || empty($_GET['taxonomy'])) {
            return false;
        }
        return $_GET['taxonomy'] === $this->slug;
    }


    /**
     * - Делает страницу списка на всю ширину
     */
    protected function fullPage()
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
            body.taxonomy-{$this->slug} #col-left,
            body.taxonomy-{$this->slug} #col-right {
                float: none!important;
                width: 100%!important;
            }
            body.taxonomy-{$this->slug}:not(.add-new) #col-left {
                display: none!important;
            }
            body.taxonomy-{$this->slug}.add-new #col-right,
            body.taxonomy-{$this->slug}.add-new .page-title-action,
            body.taxonomy-{$this->slug}.add-new .search-box,
            body.taxonomy-{$this->slug}.add-new #screen-meta-links {
                display: none!important;
            }
        ");

        $this->theAdminScript("
            jQuery('body.taxonomy-{$this->slug} .wp-heading-inline')
                .after('<a href=\"edit-tags.php?taxonomy={$this->slug}&post_type={$_GET['post_type']}&add-new=1\" class=\"page-title-action\">Добавить</a>');
        ");
    }


    protected function onAfterUpdate(callable $callback)
    {
        add_action('saved_' . $this->slug, function ($termId, $termTaxId, $update, $args) use ($callback) {
            HelperFn::execCallback($callback, $termId, $termTaxId, $update, $args);
        }, 10, 4);
    }
}
