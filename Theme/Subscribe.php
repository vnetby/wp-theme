<?php

namespace Vnet\Theme;

use Vnet\Constants\Meta;
use Vnet\Entities\PostTour;

class Subscribe
{

    private static $tax = 'subscriptions';


    static function setup()
    {
        self::addHooks();
    }


    static function addHooks()
    {
        add_action('init', [__CLASS__, 'registerTaxonomy']);
        add_action('admin_menu', [__CLASS__, 'addAdminPage']);
        add_action('parent_file', [__CLASS__, 'hilightAdminMenu']);
        add_filter('manage_edit-' . self::$tax . '_columns', [__CLASS__, 'filterAdminColumns']);
        add_filter('manage_' . self::$tax . '_custom_column', [__CLASS__, 'setAdminColumnsValue'], 10, 3);
        add_action('admin_head', [__CLASS__, 'hideAdminFields']);

        // add_action(self::$tax . "_pre_add_form", [__CLASS__, 'subscribsHeading']);
        add_action('acf/render_field/name=depart_mailinig', [__CLASS__, 'renderMailingDepart']);
    }


    /**
     * - Выводит поле для запуска подписки
     */
    static function renderMailingDepart($field)
    {
        Template::theTemplate('subscribs/mailing-depart');
    }


    static function subscribsHeading($tax)
    {
        Template::theTemplate('subscribs/heading');
    }


    static function registerTaxonomy()
    {
        register_taxonomy(self::$tax, [], [
            'label'                 => 'Подписки', // определяется параметром $labels->name
            'labels'                => [
                'name'              => 'Подписки',
                'singular_name'     => 'Подписка',
                'search_items'      => 'Искать подписки',
                'all_items'         => 'Все подписки',
                'view_item '        => 'Открыть подписку',
                'parent_item'       => 'Родительская подписка',
                'parent_item_colon' => 'Родительская подписка:',
                'edit_item'         => 'Изменить подписку',
                'update_item'       => 'Обновить подписку',
                'add_new_item'      => 'Добавить подписку',
                'new_item_name'     => 'Новое название подписки',
                'menu_name'         => 'Подписки',
                'back_to_items'     => '← Назад к списку',
            ],
            'description'           => '', // описание таксономии
            'public'                => true,
            'publicly_queryable'    => false, // равен аргументу public
            'show_in_nav_menus'     => false, // равен аргументу public
            // 'show_ui'               => true, // равен аргументу public
            // 'show_in_menu'          => true, // равен аргументу show_ui
            // 'show_tagcloud'         => true, // равен аргументу show_ui
            // 'show_in_quick_edit'    => null, // равен аргументу show_ui
            'hierarchical'          => false,
            'rewrite'               => true,
            //'query_var'             => $taxonomy, // название параметра запроса
            'capabilities'          => [],
            'meta_box_cb'           => null, // html метабокса. callback: `post_categories_meta_box` или `post_tags_meta_box`. false — метабокс отключен.
            'show_admin_column'     => false, // авто-создание колонки таксы в таблице ассоциированного типа записи. (с версии 3.5)
            'show_in_rest'          => null, // добавить в REST API
            'rest_base'             => null, // $taxonomy
            // '_builtin'              => false,
            //'update_count_callback' => '_update_post_term_count',
        ]);
    }


    static function setAdminColumnsValue($content, $colName, $termId)
    {
        if ($colName !== 'tour') {
            return $content;
        }

        $tourId = get_term_meta($termId, 'tour_notif', true);

        if (!$tourId) {
            return "&mdash;";
        }

        $tour = PostTour::getById((int)$tourId);

        if (!$tour) {
            return "&mdash;";
        }

        return "<a href='{$tour->getAdminUrl()}'>{$tour->getTitle()}</a>";
    }


    static function filterAdminColumns($columns)
    {
        if (isset($columns['wpseo-score'])) {
            unset($columns['wpseo-score']);
        }

        if (isset($columns['wpseo-score-readability'])) {
            unset($columns['wpseo-score-readability']);
        }

        if (isset($columns['slug'])) {
            unset($columns['slug']);
        }

        if (isset($columns['posts'])) {
            unset($columns['posts']);
        }

        $columns['tour'] = 'Тур';

        return $columns;
    }


    static function hideAdminFields()
    {
        if (!self::isAdminEditPage()) {
            return;
        }
?>
        <style>
            .term-slug-wrap,
            .wpseo-taxonomy-metabox-postbox {
                display: none !important;
            }
        </style>
<?php
    }


    /**
     * - Добавляет страницы в админке
     */
    static function addAdminPage()
    {
        if (self::isAdminEditPage()) {
            self::updateViewed();
        }

        if ($new = self::countNew()) {
            $label = '
            Подписки 
            <span class="update-plugins count-' . $new . '">
                <span class="plugin-count" aria-hidden="true">' . $new . '</span>
                <span class="screen-reader-text">' . $new . ' подписки</span>
            </span>
            ';
        } else {
            $label = 'Подписки';
        }
        add_submenu_page(
            'tools.php',
            'Подписки',
            $label,
            'manage_options',
            'edit-tags.php?taxonomy=' . self::$tax,
        );
    }


    /**
     * - Подсвечивает нужный пункт меню в админке
     */
    static function hilightAdminMenu($parentFile)
    {
        global $current_screen;

        $tax = $current_screen->taxonomy;

        if ($tax === self::$tax) {
            $parentFile = 'tools.php';
        }

        return $parentFile;
    }


    /**
     * - Добавляет подписку
     * 
     * @param string $email
     * @param string $tourId [optional] подписка к туру
     * 
     * @return false|\WP_Term
     */
    static function add($email, $tourId = null)
    {
        $term = self::get($email);

        if (!$term) {
            $term = self::insert($email);
        }

        if (!$term) {
            return false;
        }

        if ($tourId) {
            update_term_meta($term->term_id, 'tour_notif', $tourId);
        } else {
            delete_term_meta($term->term_id, 'tour_notif');
        }

        return $term;
    }


    /**
     * @return false|\WP_Term
     */
    static function insert($email)
    {
        $res = wp_insert_term($email, self::$tax);
        return is_wp_error($res) ? false : self::get($email);
    }


    /**
     * - Получает термин подписки
     * 
     * @param string $email
     * 
     * @return \WP_Term|false
     */
    static function get($email)
    {
        return get_term_by('name', $email, self::$tax);
    }


    /**
     * - Получает секретный ключ для отписки
     * @param string $email
     * @param bool $generate - сгенерировать ключ если его еще нет
     * 
     * @return null|string
     */
    static function getUnsubscribeSecret(string $email, $generate = true): ?string
    {
        $term = self::get($email);

        if (!$term) {
            return null;
        }

        $key = get_term_meta($term->term_id, Meta::UNSUBSCRIBE_SECRET, true);

        if ($key || !$generate) {
            return $key;
        }

        $key = md5(serialize($term));

        update_term_meta($term->term_id, Meta::UNSUBSCRIBE_SECRET, $key);

        return $key;
    }


    /**
     * - Отписывает пользователя по секретному ключу
     */
    static function unsubscribe(string $key): bool
    {
        $args = [
            'taxonomy' => self::$tax,
            'hide_empty' => false,
            'meta_query' => [
                [
                    'key' => Meta::UNSUBSCRIBE_SECRET,
                    'value' => $key,
                    'compare' => 'LIKE'
                ]
            ]
        ];

        $terms = get_terms($args);

        if (!$terms) {
            return true;
        }

        foreach ($terms as $term) {
            // в случае если есть дубли
            $allTerms = get_terms(['taxonomy' => self::$tax, 'hide_empty' => false, 'name' => $term->name]);
            foreach ($allTerms as $curTerm) {
                wp_delete_term($curTerm->term_id, self::$tax);
            }
        }

        return true;
    }


    static function getUnsubscribeUrl(string $email): ?string
    {
        $key = self::getUnsubscribeSecret($email, true);
        if (!$key) {
            return null;
        }
        return site_url('/unsubscribe/' . $key . '/');
    }


    /**
     * - Получает термин подписки
     * 
     * @param string $id
     * 
     * @return \WP_Term|false
     */
    static function getById($id)
    {
        return get_term_by('term_id', $id, self::$tax);
    }

    /**
     * - Считает кол-во подписок
     * 
     * @return int
     */
    static function count()
    {
        $res = wp_count_terms(['taxonomy' => self::$tax, 'hide_empty' => false]);

        if (is_wp_error($res)) {
            return 0;
        }

        return (int)$res;
    }


    /**
     * - Считает новые подписки
     * - Используется в админке
     * 
     * @return int
     */
    private static function countNew()
    {
        $viewed = self::getViewed();
        $count = self::count();
        $new = $count - $viewed;
        return $new > 0 ? $new : 0;
    }

    /**
     * - Получает кол-во просмотренных подписок
     * - Используется в админке
     * 
     * @return int
     */
    private static function getViewed()
    {
        return (int)get_option('subscribs_viewed', 0);
    }

    /**
     * - Обновляет кол-во просмотренных подписок
     * - используется в админке
     */
    private static function updateViewed()
    {
        $count = self::count();
        update_option('subscribs_viewed', $count);
    }


    private static function isAdminEditPage()
    {
        if (!is_admin()) {
            return false;
        }
        if (empty($_GET['taxonomy'])) {
            return false;
        }
        return $_GET['taxonomy'] === self::$tax;
    }


    /**
     * - Получает подписки на уведомления по выезду
     */
    static function getTourSubscribs()
    {
        global $wpdb;
        $tableTerms = $wpdb->terms;
        $tableMeta = $wpdb->termmeta;
        $tableTax = $wpdb->term_taxonomy;
        $tax = self::$tax;

        $query = "SELECT * FROM $tableTerms
            INNER JOIN $tableTax ON $tableTerms.term_id = $tableTax.term_id
            WHERE $tableTax.taxonomy = '$tax'
        ";

        // $query = "SELECT * FROM $tableTerms
        // INNER JOIN $tableTax ON $tableTerms.term_id = $tableTax.term_id
        // LEFT JOIN $tableMeta ON $tableMeta.term_id = $tableTerms.term_id
        // WHERE $tableTax.taxonomy = '$tax' AND $tableMeta.meta_key = 'exist_email' AND $tableMeta.meta_value = 1
        // ";

        $subscribs = $wpdb->get_results($query, ARRAY_A);

        if (!$subscribs || is_wp_error($subscribs)) {
            return [];
        }

        return $subscribs;
    }


    /**
     * - Получает подписки на валидацию emaila
     */
    static function getTourSubscribsCheckEmails()
    {
        global $wpdb;
        $tableTerms = $wpdb->terms;
        $tableMeta = $wpdb->termmeta;
        $tableTax = $wpdb->term_taxonomy;
        $tax = self::$tax;

        $query = "SELECT * FROM $tableTerms
            INNER JOIN $tableTax ON $tableTerms.term_id = $tableTax.term_id
            WHERE $tableTax.taxonomy = '$tax'
        ";

        // $query = "SELECT * FROM $tableTerms
        // INNER JOIN $tableTax ON $tableTerms.term_id = $tableTax.term_id
        // LEFT JOIN $tableMeta ON $tableMeta.term_id = $tableTerms.term_id
        // WHERE $tableTax.taxonomy = '$tax' AND $tableMeta.meta_key = 'checkout_email' AND $tableMeta.meta_value = 1
        // ";


        $subscribs = $wpdb->get_results($query, ARRAY_A);

        if (!$subscribs || is_wp_error($subscribs)) {
            return [];
        }

        return $subscribs;
    }
}
