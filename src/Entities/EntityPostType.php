<?php

namespace Vnetby\Wptheme\Entities;

use Vnetby\Helpers\HelperArr;
use Vnetby\Helpers\HelperFn;
use Vnetby\Wptheme\Helpers\HelperAcf;

abstract class EntityPostType extends Entity
{

    function setup()
    {
        $this->wpItem = get_post_type_object($this->getKey());
    }


    /**
     * - Добавляет роут для страниц данного типа поста
     * - Для роута отдельной страницы должен присутствовать обязательный параметр name
     * с регулярным выражением: ([^/]+)
     * @param string $regex регулярное вырожение которому должна соответствовать ссылка
     * @param string[] $queryVars мыссив урл параметров. Можно будет получить через get_query_var()
     * порядок должен соответстовать порядку группам в регулярном выражении
     * @param string $after 
     * @return void 
     */
    protected function addRoute(string $regex, array $queryVars, $after = 'top')
    {
        add_action('init', function () use ($regex, $queryVars, $after) {
            $query = '';

            foreach ($queryVars as $i => $var) {
                $query .= '&' . $var . '=$matches[' . ($i + 1) . ']';
            }

            $url = 'index.php?post_type=' . $this->getKey() . $query;

            add_rewrite_rule($regex, $url, $after);
        });

        add_filter('query_vars', function ($vars) use ($queryVars) {
            $vars = array_merge($vars, $queryVars);
            return $vars;
        });
    }


    /**
     * - Заголовок будет генерироваться автоматически
     * @param callable $getTitle функция для получения заголовка
     * @return void 
     */
    protected function autoTitle(callable $getTitle)
    {
        $this->theAdminStyle("
            body.post-type-{$this->getKey()} #titlediv #titlewrap {
                display: none!important;
            }
        ");

        $this->theAdminScript("
            let title = document.createElement('h1');
            title.innerHTML = jQuery('body.post-type-{$this->getKey()} #titlediv #titlewrap input[type=\"text\"]').val();
            jQuery('body.post-type-{$this->getKey()} #titlediv #titlewrap').before(title);
        ");

        add_filter('wp_insert_post_data', function ($data) use ($getTitle) {
            if ($data['post_type'] !== $this->getKey()) {
                return $data;
            }
            $data['post_title'] = call_user_func($getTitle, $data);
            return $data;
        }, 99);
    }


    protected function addImgColumn(string $position = 'title', string $label = 'Картинка', ?callable $callback = null)
    {
        if ($callback === null) {
            $callback = function ($postId) {
                if ($img = get_the_post_thumbnail_url($postId)) {
                    echo '<img src="' . $img . '" style="width: 120px;">';
                }
            };
        }
        $this->addColumn('image', $label, $callback, $position);
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
        add_filter("manage_{$this->getKey()}_posts_columns", function (array $columns) use ($colKey, $position, $colLabel) {
            if ($position === 'last') {
                $position = array_keys($columns)[count(array_keys($columns)) - 1];
            } else if ($position === 'first') {
                $position = 'cb';
            }
            $columns = HelperArr::insert($columns, $position, [$colKey => $colLabel]);
            return $columns;
        });
        add_action("manage_{$this->getKey()}_posts_custom_column", function ($column, $postId) use ($colKey, $getValCallback) {
            if ($column === $colKey) {
                echo call_user_func($getValCallback, (int)$postId);
            }
        }, 10, 3);
    }


    /**
     * - Удаляет колонку
     * @param string $colKey 
     * @return void 
     */
    protected function removeColumn(string $colKey)
    {
        add_filter("manage_{$this->getKey()}_posts_columns", function (array $columns) use ($colKey) {
            if (isset($columns[$colKey])) {
                unset($columns[$colKey]);
            }
            return $columns;
        });
    }


    protected function sortableColumn(string $colKey, callable $sortFunction)
    {
        add_filter("manage_edit-{$this->getKey()}_sortable_columns", function ($columns) use ($colKey) {
            $columns[$colKey] = $colKey;
            return $columns;
        });

        if (is_admin()) {
            add_action('pre_get_posts', function (\WP_Query $query) use ($colKey, $sortFunction) {
                if (!$query->is_main_query() || $query->query['post_type'] !== $this->getKey() || $query->get('orderby') !== $colKey) {
                    return;
                }
                call_user_func($sortFunction, $query);
            });
        }
    }


    /**
     * - Устанавливает сортировку по умолчанию
     * @param callable $sortFunction 
     * @return void 
     */
    protected function defaultSort(callable $sortFunction)
    {
        if (is_admin()) {
            $this->setQuerySort($sortFunction);
        }
    }


    protected function defaultFrontSort(callable $sortFunction)
    {
        if (!is_admin()) {
            $this->setQuerySort($sortFunction);
        }
    }


    private function setQuerySort(callable $sortFunction)
    {
        add_action('pre_get_posts', function (\WP_Query $query) use ($sortFunction) {
            if (!$query->is_main_query() || empty($query->query['post_type']) || $query->query['post_type'] !== $this->getKey() || !!$query->get('orderby')) {
                return;
            }
            call_user_func($sortFunction, $query);
        });
    }


    /**
     * - Добавляет фильтр на странице списка в админке
     * @param callable $drawCallback функция которая отрисовывает элемент
     * @param callable $filterCallback функция которая изменяет WP_Query (добавляет параметры фильтра)
     * @return void 
     */
    protected function addAdminFilter(callable $drawCallback, callable $filterCallback)
    {
        add_action('restrict_manage_posts', function () use ($drawCallback) {
            global $pagenow;
            // на страницце списка постов в админке, данный параметр отсутвует
            if (empty($_GET['post_type'])) {
                return;
            }
            if (!is_admin() || $pagenow !== 'edit.php' || $_GET['post_type'] !== $this->getKey()) {
                return;
            }
            call_user_func($drawCallback);
        });

        add_filter('parse_query', function (\WP_Query $query) use ($filterCallback) {
            global $pagenow;
            // на страницце списка постов в админке, данный параметр отсутвует
            if (empty($_GET['post_type'])) {
                return;
            }
            if (!is_admin() || $pagenow !== 'edit.php' || $_GET['post_type'] !== $this->getKey() || !$query->is_main_query()) {
                return;
            }
            call_user_func($filterCallback, $query);
        });
    }


    protected function removeAdminFilter(string $filterId)
    {
        $this->theAdminStyle("
            .tablenav #{$filterId} {
                display: none!important;
            }
        ");
    }


    protected function menuColorOrange()
    {
        $this->menuColor('orange');
    }


    protected function menuColorGreen()
    {
        $this->menuColor('green');
    }


    /**
     * - Устанавливает цвет пункту меню
     * @param string $color orange|green
     */
    protected function menuColor(string $color)
    {
        $this->addMenuClass('theme-menu-item', "c-{$color}");
    }


    protected function menuAwesomeIco(string $ico)
    {
        add_action('admin_enqueue_scripts', function () {
            wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css');
        });

        $this->addMenuClass('theme-menu-item', 'awesome-icon');

        $this->theAdminStyle("
            .theme-menu-item.awesome-icon#menu-posts-{$this->getKey()} .wp-menu-image {
                font-family: \"Font Awesome 5 Free\";
            }
            .theme-menu-item.awesome-icon#menu-posts-{$this->getKey()} .wp-menu-image:before {
                content: \"\\{$ico}\";
                font-family: inherit;
                font-weight: bold;
            }
        ");
    }


    /**
     * - Скрывает создание и список
     * - Перенаправляет на страницу таксономии
     * @param string $tax
     */
    protected function groupTaxonomies(string $tax)
    {
        $url = '/wp-admin/edit-tags.php?taxonomy=' . $tax . '&post_type=' . $this->getKey();

        // скрываем пункты меню
        $this->theAdminStyle("
            #menu-posts-{$this->getKey()} .wp-submenu .wp-first-item,
            #menu-posts-{$this->getKey()} .wp-submenu .wp-first-item + li {
                display: none!important;
            }
        ");

        // меняем основную ссылку
        $this->theAdminScript("
            jQuery('#menu-posts-{$this->getKey()} a').first().attr('href', '{$url}');
        ");

        // выполняем редирект если пользователь попал на скрытые страницы

        $file = basename($_SERVER['SCRIPT_NAME']);

        if (empty($_GET['post_type']) || !in_array($file, ['edit.php', 'post-new.php'])) {
            return;
        }

        if ($_GET['post_type'] !== $this->getKey()) {
            return;
        }

        wp_redirect($url);

        exit;
    }


    /**
     * - Устанавливает кол-во постов на странице архива на фронте
     * @param int $perPage
     */
    function perPage(int $perPage)
    {
        add_filter('pre_get_posts', function (\WP_Query $query) use ($perPage) {
            if (!$query->is_post_type_archive($this->getKey()) || is_admin() || !$query->is_main_query()) {
                return $query;
            }
            $query->set('posts_per_page', $perPage);
        });
    }


    protected function filterExcerpt(callable $fn, int $priority = 10)
    {
        add_filter('get_the_excerpt', function (string $exerpt, \WP_Post $post) use ($fn) {
            if ($post->post_type !== $this->getKey()) {
                return $exerpt;
            }
            return call_user_func($fn, $exerpt, $post);
        }, $priority, 2);
    }


    protected function filterTitle(callable $fn, int $priority = 10)
    {
        if (is_admin()) {
            return;
        }
        add_filter('the_title', function (string $title, int $postId) use ($fn) {
            if (get_post_type($postId) !== $this->getKey()) {
                return $title;
            }
            return call_user_func($fn, $title, $postId);
        }, $priority, 2);
    }


    protected function filterPermalink(callable $fn, int $priority = 10)
    {
        if (is_admin()) {
            return;
        }
        add_filter('post_type_link', function (string $permalink, \WP_Post $post, bool $leavename) use ($fn) {
            if ($post->post_type !== $this->getKey()) {
                return $permalink;
            }
            return call_user_func($fn, $permalink, $post, $leavename);
        }, $priority, 3);
    }


    /**
     * - Добавляет метабокс
     * @param string $id 
     * @param string $title 
     * @param callable $drawCallback 
     * @param callable $updateCallback 
     * @param string $context 
     * @param string $priority 
     */
    protected function addMetabox(string $id, string $title, callable $drawCallback, callable $updateCallback = null, string $context = 'advanced', string $priority = 'default')
    {
        add_action('add_meta_boxes', function () use ($id, $title, $drawCallback, $context, $priority) {
            add_meta_box($id, $title, $drawCallback, $this->getKey(), $context, $priority);
        });

        if ($updateCallback) {
            add_action('save_post_' . $this->getKey(), function (int $postId, \WP_Post $post, bool $update) use ($updateCallback) {
                call_user_func($updateCallback, $postId, $post, $update);
            }, 10, 3);
        }
    }


    /**
     * - Хук после обновления элемента
     * @see https://wp-kama.com/hook/post_updated
     * @param callable $callback 
     * @return void 
     */
    protected function onUpdated(callable $callback)
    {
        add_action('post_updated', function (int $postId, \WP_Post $postAfter, \WP_Post $postBefore) use ($callback) {
            if ($postAfter->post_type === $this->getKey()) {
                call_user_func($callback, $postId, $postAfter, $postBefore);
            }
        }, 10, 3);
    }


    /**
     * - Срабатывает всякий раз, когда запись (пост, страница) создается или обновляется
     * @see https://wp-kama.ru/hook/save_post
     * @param callable $callback 
     * @return void 
     */
    protected function onSave(callable $callback)
    {
        $slug = $this->getKey();
        add_action("save_post_{$slug}", $callback, -1, 3);
    }


    /**
     * - Срабатывает после обновления полей ACF
     * @see https://www.advancedcustomfields.com/resources/acf-save_post/
     * @param callable $callback 
     * @return void 
     */
    protected function onAcfSave(callable $callback)
    {
        add_action('acf/save_post', function ($postId) use ($callback) {
            if (!is_numeric($postId)) {
                return;
            }
            if (get_post_type($postId) !== $this->getKey()) {
                return;
            }
            call_user_func($callback, $postId);
        }, 10, 1);
    }


    /**
     * - Добаляет доп классы строке элемента в таблице списка
     * @param callable $callback должен вернуть массив строк
     * @return void 
     */
    protected function rowClass(callable $callback)
    {
        add_filter('post_class', function (array $classes, array $class, int $postId) use ($callback) {
            if (!is_admin()) {
                return $classes;
            }
            $screen = get_current_screen();
            if ($screen->post_type !== $this->getKey() || $screen->base !== 'edit') {
                return $classes;
            }
            return array_merge($classes, call_user_func($callback, $postId));
        }, 10, 3);
    }


    /**
     * - Должен быть вызван вначале!
     * - Устанавливает значение колонки в таблице posts из значения ACF поля
     * @param string $acfName название ACF поля
     * @param string $postField название колонки из таблицы posts
     * @return void 
     */
    protected function acfToPostField(string $acfName, string $postField)
    {
        $postField = strtolower($postField);

        $allowFields = [
            'post_title',
            'post_author',
            'post_content',
            'post_excerpt'
        ];

        if (!in_array($postField, $allowFields)) {
            return;
        }

        add_filter("acf/load_value/name={$acfName}", function ($value, int $postId) use ($postField) {
            if (get_post_type($postId) !== $this->getKey()) {
                return $value;
            }
            $post = get_post($postId);
            if (!$post || !property_exists($post, $postField)) {
                return $value;
            }
            return $post->$postField ?: $value;
        }, 10, 2);

        add_filter("acf/update_value/name={$acfName}", function ($value, int $postId) use ($acfName, $postField) {
            if (get_post_type($postId) !== $this->getKey()) {
                return $value;
            }
            if (is_string($value) || is_numeric($value)) {
                $GLOBALS['acf_top_post_field_' . $postField . '_' . $acfName . '_' . $postId] = $value;
                return null;
            }
            return $value;
        }, 10, 2);

        $this->onAcfSave(function (int $postId) use ($acfName, $postField) {
            if (get_post_type($postId) !== $this->getKey()) {
                return;
            }
            $value = $GLOBALS['acf_top_post_field_' . $postField . '_' . $acfName . '_' . $postId] ?? '';
            wp_update_post(['ID' => $postId, $postField => $value]);
            if (isset($GLOBALS['acf_top_post_field_' . $postField . '_' . $acfName . '_' . $postId])) {
                unset($GLOBALS['acf_top_post_field_' . $postField . '_' . $acfName . '_' . $postId]);
            }
        });
    }


    /**
     * - Должен быть вызван самым первым!
     * - Сохраняет и получает значения всех переданных ACF полей в колонке post_content_filtered
     *   таблицы wp_posts, тем самым уменьшая кол-во записей в таблице postmeta.
     * - Данный метод можно применять только для ACF полей которые никак не участвуют в выборке постов из БД
     *   например сео описание или сео заголовок.
     * @param string $acfNames имя или ключи полей
     * @return void 
     */
    protected function acfToPostContentFiltered(string ...$acfNames)
    {
        $this->onAcfSave(function (int $postId) use ($acfNames) {
            $acfValues = [];
            $globalKey = 'prevent_acf_load_value_filter_' . $postId;
            $GLOBALS[$globalKey] = true;

            foreach ($acfNames as $acfName) {
                $field = acf_get_field($acfName);

                if (!$field) {
                    continue;
                }

                $acfName = $field['name'];

                $acfValues[$field['key']] = get_field($field['key'], $postId, false);

                delete_field($field['key'], $postId);
            }

            wp_update_post([
                'ID' => $postId,
                'post_content_filtered' => serialize($acfValues)
            ]);

            unset($GLOBALS[$globalKey]);
        });

        foreach ($acfNames as $acfName) {
            $hook = preg_match("/^acf_/", $acfName) ? 'key=' . $acfName : 'name=' . $acfName;

            add_filter("acf/load_value/{$hook}", function ($value, int $postId, array $field) use ($acfName) {
                $globalKey = 'prevent_acf_load_value_filter_' . $postId;

                if (get_post_type($postId) !== $this->getKey() || !empty($GLOBALS[$globalKey])) {
                    return $value;
                }

                $acfKey = $field['key'];
                $filter = false;

                // если не переадны данные поля - значит запрос с фронта
                if (!$acfKey) {
                    $filter = true;
                    $acfKey = acf_get_field($acfName)['key'];
                }

                $post = get_post($postId);

                $content = $post->post_content_filtered ? unserialize($post->post_content_filtered) : [];

                $resVal = isset($content[$acfKey]) ? $content[$acfKey] : $value;

                if ($filter) {
                    return HelperAcf::formatAcfValue($resVal);
                }

                return $resVal;
            }, 10, 3);
        }
    }


    protected function filterArchiveMainQuery(callable $callback)
    {
        add_action('pre_get_posts', function (\WP_Query $query) use ($callback) {
            if (!$query->is_main_query() || !$query->is_post_type_archive($this->getKey())) {
                return;
            }
            HelperFn::execCallback($callback, $query);
        });
    }
}
