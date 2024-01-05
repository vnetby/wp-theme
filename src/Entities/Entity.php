<?php

namespace Vnetby\Wptheme\Entities;

use Error;
use Vnetby\Wptheme\Models\Model;
use Vnetby\Wptheme\Models\Post;
use Vnetby\Wptheme\Models\Term;

abstract class Entity
{

    /**
     * - Уникальный ключ сущности
     * - В случае с таксономией - это таксономия
     * - В случае с типом поста - это тип поста
     * @var string
     */
    protected string $key;

    /**
     * - Объект wordpress
     * @var \WP_Post_Type|\WP_Taxonomy
     */
    protected $wpItem;

    /**
     * - Класс модели для работы с элементом
     * @var class-string<Model>
     */
    protected string $model;

    /**
     * - Создает объект сущности
     * - Вызывается после того как была загружена вся тема
     * @param string $key Уникальный ключ сущности
     *   В случае с таксономией - это таксономия
     *   В случае с типом поста - это тип поста
     */
    function __construct(string $key)
    {
        $this->key = $key;
        if (!isset($this->model)) {
            $this->model = $this->isPostType() ? Post::class : Term::class;
        }
    }

    /**
     * - Инициализирует сущность
     * - Вызывается после того как были созданы объекты всех сущностей
     */
    abstract function setup();


    function getKey(): string
    {
        return $this->key;
    }

    function getModelClass(): string
    {
        return $this->model;
    }

    function isPostType(): bool
    {
        return ($this instanceof PostType);
    }

    function isTaxonomy(): bool
    {
        return ($this instanceof Taxonomy);
    }

    protected function theAdminStyle(string $css)
    {
        add_action('admin_head', function () use ($css) {
            $this->theStyle($css);
        });
    }


    protected function register(array $params, array $posts = [])
    {
        add_action('init', function () use ($params, $posts) {
            if ($this->isPostType()) {
                $wpItem = register_post_type($this->getKey(), $this->filterPostTypeParams($params));
            } else {
                $wpItem = register_taxonomy($this->getKey(), $posts, $this->filterTaxParams($params));
            }
            if (is_wp_error($wpItem)) {
                throw new Error(implode(PHP_EOL, $wpItem->get_error_messages()));
            }
            $this->wpItem = $wpItem;
        });
    }


    protected function filterPostTypeParams(array $params): array
    {
        $defLabels = [
            'name' => __('Элементы', 'vnet'),
            'singular_name' => __('Элемент', 'vnet'),
            'add_new' => __('Добавить элемент', 'vnet'),
            'add_new_item' => __('Добавление элемента', 'vnet'),
            'edit_item' => __('Редактирование элемента', 'vnet'),
            'new_item' => __('Новый элемент', 'vnet'),
            'view_item' => __('Смотреть элемент', 'vnet'),
            'search_items' => __('Искать элемент', 'vnet'),
            'not_found' => __('Не найдено', 'vnet'),
            'not_found_in_trash' => __('Не найдено в корзине', 'vnet'),
            'parent_item_colon' => __('Родительский элемент:', 'vnet'),
            'menu_name' => __('Элементы', 'vnet')
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

        $params['public'] = $params['public'] ?? true;
        $params['has_archive'] = $params['has_archive'] ?? true;
        $params['query_var'] = $params['query_var'] ?? false;
        $params['supports'] = $params['supports'] ?? ['title', 'thumbnail', 'excerpt', 'editor'];

        return $params;
    }


    protected function filterTaxParams(array $params): array
    {
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


    protected function theAdminScript(string $js, $onLoad = false)
    {
        add_action('admin_footer', function () use ($js, $onLoad) {
            if ($onLoad) {
                $this->theScriptOnLoad($js);
            } else {
                $this->theScript($js);
            }
        });
    }


    protected function theStyle(string $css)
    {
        echo '<style>' . $css . '</style>';
    }


    protected function theScript(string $js)
    {
        echo '<script>' . $js . '</script>';
    }


    protected function theScriptOnLoad(string $js)
    {
        echo '<script> window.addEventListener("DOMContentLoaded", function () { ' . $js . ' }); </script>';
    }


    /**
     * - Добавляет класс к пункту меню
     * @param string[] $class 
     */
    protected function addMenuClass(...$class)
    {
        add_action('admin_init', function () use ($class) {
            global $menu;

            if (!$menu) {
                return;
            }

            foreach ($menu as &$item) {
                if (!isset($item[5])) {
                    continue;
                }

                if ($item[5] !== 'menu-posts-' . $this->getKey()) {
                    continue;
                }

                $item[4] = implode(' ', array_merge(explode(' ', $item[4]), $class));

                break;
            }
        });
    }


    /**
     * - Получает текущий элемент
     * @return Post|Term|null
     */
    function getCurrentElement()
    {
        $model = $this->getModelClass();
        return $model::getCurrent();
    }
}
