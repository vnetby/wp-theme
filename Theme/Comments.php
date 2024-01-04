<?php

namespace Vnet\Theme;

use Vnet\Cache;
use Vnet\Constants\Meta;
use Vnet\Entities\Comment;

class Comments
{
    private static $avatars = null;


    /**
     * - Инициализация хуков и прочее
     */
    static function setup()
    {
        add_action('edit_comment', function (int $commentId) {
            $comment = Comment::getById($commentId);
            if (!$comment) {
                return;
            }
            if (isset($_POST['rating'])) {
                $comment->updateRating((float)$_POST['rating']);
            }
            $comment->recountPostRating();
        }, 10, 1);

        add_action('wp_set_comment_status', function (int $commentId) {
            if ($comment = Comment::getById($commentId)) {
                $comment->recountPostRating();
            }
        }, 10, 1);
    }


    static function getSiteRating(): float
    {
        return self::getPostRating(get_option('page_on_front'));
    }


    static function getPostRating(int $postId): float
    {
        $rating = get_post_meta($postId, Meta::POST_RATING, true);
        $rating = $rating ? (float)$rating : 0;
        return $rating;
    }

    static function updatePostRating(int $postId, float $rating)
    {
    }


    /**
     * - Считает комментарии к отдельному посту
     * @return int 
     */
    static function countPostComments(int $postId = null): int
    {
        global $wpdb;
        $table = $wpdb->comments;

        if ($postId === null) {
            $postId = (int)get_option('page_on_front');
        }

        $res = (int)$wpdb->get_var("SELECT COUNT(`comment_ID`) FROM `{$table}` WHERE `comment_approved` = 1 AND `comment_post_ID` = {$postId}");

        return $res;
    }

    /**
     * - Получает комментарии к сайту
     */
    static function getSiteComments($page = 1, $perPage = 10)
    {
        $postId = (int)get_option('page_on_front');
        return self::getComments($postId, $page, $perPage);
    }


    static function getComments($postId, $page = 1, $perPage = 5)
    {
        $cacheKey = \Vnet\Constants\Cache::COMMENTS_LIST . $postId . $page . $perPage;

        return Cache::fetch($cacheKey, function () use ($postId, $page, $perPage) {
            global $wpdb;

            $offset = $perPage * ($page - 1);

            $query = "FROM {$wpdb->comments} 
            WHERE `comment_post_ID` = $postId
            AND `comment_approved` = 1 
            ORDER BY `comment_date` DESC";

            $querySelect = "SELECT * $query LIMIT $perPage OFFSET $offset";
            $queryCount = "SELECT COUNT(`comment_ID`) $query";

            $res = $wpdb->get_results($querySelect, ARRAY_A);
            $total = (int)$wpdb->get_var($queryCount);

            if (!$res || is_wp_error($res)) {
                return [
                    'comments' => [],
                    'total' => $total,
                    'hasMore' => false
                ];
            }

            $comments = [];

            foreach ($res as $data) {
                $comments[] = Comment::getByData($data);
            }

            $totalPages = ceil($total / $perPage);

            return [
                'comments' => $comments,
                'total' => $total,
                'hasMore' => $page < $totalPages
            ];
        });
    }


    /**
     * - Вставляет новый комментари
     * @see https://wp-kama.com/function/wp_new_comment
     * @param array $commentData 
     * @param array $meta массив мета данных
     * @return null|Comment null вслучае ошибки, объект комментария при успехе
     */
    static function addComment(array $commentData, array $meta = []): ?Comment
    {
        if (!isset($commentData['comment_author_url'])) {
            $commentData['comment_author_url'] = '';
        }

        if (!isset($commentData['comment_author_email'])) {
            $commentData['comment_author_email'] = '';
        }

        $commentId = wp_new_comment($commentData, true);

        if (is_wp_error($commentId)) {
            return null;
        }

        foreach ($meta as $metaKey => $metaValue) {
            update_comment_meta($commentId, $metaKey, $metaValue);
        }

        $comment = Comment::getById($commentId);

        if (!$comment) {
            return null;
        }

        $comment->recountPostRating();

        return $comment;
    }


    /**
     * - Получает возможные аватарки для комментариев
     * @return string[]
     */
    static function getAvatars()
    {
        if (self::$avatars === null) {
            self::setAvatars();
        }
        return self::$avatars;
    }


    private static function setAvatars()
    {
        $scan = scandir(THEME_PATH . 'img/avatars');
        $res = [];

        foreach ($scan as $fileName) {
            if (in_array($fileName, ['.', '..'])) {
                continue;
            }

            // аватарки которые не используются
            // начинаются на _
            if (preg_match("/^_/", $fileName)) {
                continue;
            }

            $path = THEME_PATH . 'img/avatars/' . $fileName;

            if (!is_file($path)) {
                continue;
            }

            $res[] = [
                'path' => $path,
                'url' => THEME_URI . 'img/avatars/' . $fileName,
                'id' => base64_encode($fileName)
            ];
        }

        self::$avatars = $res;
    }
}
