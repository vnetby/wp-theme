<?php

namespace Vnet\Entities;

use Vnet\Constants\Meta;
use Vnet\Helpers\Date;

class Comment
{

    private $data = [];


    /**
     * - Получает объект комментария по выборке из базы
     * @param array $data 
     * @return self
     */
    static function getByData(array $data): self
    {
        return new self($data);
    }

    /**
     * - Получает комментарий по его ID
     * @param int $id 
     * @return null|self 
     */
    static function getById(int $id): ?self
    {
        $data = get_comment($id, ARRAY_A);
        if (!$data) {
            return null;
        }
        return self::getByData($data);
    }


    private function __construct(array $data)
    {
        $this->data = $data;
    }


    /**
     * - Пересчитывает рейтинг поста к которому прикреплен комментарий
     */
    function recountPostRating()
    {
        global $wpdb;

        if (!$this->getPostId()) {
            return;
        }

        $postId = $this->getPostId();

        $allIds = $wpdb->get_results("SELECT `comment_ID` 
        FROM {$wpdb->comments} 
        WHERE `comment_approved` = 1
        AND `comment_post_ID` = {$postId}
        ", ARRAY_A);

        $metaRatingKey = Meta::POST_RATING;

        if (!$allIds || is_wp_error($allIds)) {
            $rating = 0;
        } else {
            $allIds = array_column($allIds, 'comment_ID');
            $sqlIds = implode(',', $allIds);

            $sum = (float)$wpdb->get_var("SELECT SUM(`meta_value`) FROM `{$wpdb->commentmeta}` WHERE `comment_id` IN ($sqlIds) AND `meta_key` = '{$metaRatingKey}'");
            $total = (int)$wpdb->get_var("SELECT COUNT(`meta_id`) FROM `{$wpdb->commentmeta}` WHERE `comment_id` IN ($sqlIds) AND `meta_key` = '{$metaRatingKey}'");

            $max = 5;
            $maxRating = $max * $total;
            $rating = round(($sum * 5 / $maxRating), 1);
        }

        update_post_meta($postId, $metaRatingKey, $rating);
    }


    /**
     * - Обновляет рейтинг комментария
     * @param float $rating 
     */
    function updateRating(float $rating): bool
    {
        $res = update_comment_meta($this->getId(), Meta::COMMENT_RATING, $rating);
        return $res !== false;
    }


    function getId(): int
    {
        return (int)($this->data['comment_ID'] ?? 0);
    }


    function getAuthorName(): string
    {
        return $this->data['comment_author'] ?? '';
    }


    function getContent(): string
    {
        return $this->data['comment_content'] ?? '';
    }


    function isApproved(): bool
    {
        return !empty($this->data['comment_approved']);
    }


    function getDate(string $format = null): string
    {
        $date = $this->data['comment_date'] ?? '';
        if (!$date || !$format) {
            return $date;
        }
        return Date::format($format, $date);
    }


    function getParent(): ?self
    {
        if (!empty($this->data['comment_parent'])) {
            return self::getById((int)$this->data['comment_parent']);
        }
        return null;
    }


    function getPostId(): int
    {
        return (int)($this->data['comment_post_ID'] ?? 0);
    }


    function getAvatarUrl(): string
    {
        if ($src = get_comment_meta($this->getId(), Meta::COMMENT_AVATAR, true)) {
            return THEME_URI . 'img/avatars/' . base64_decode($src);
        }
        return '';
    }


    function getRating(): float
    {
        if ($rating = get_comment_meta($this->getId(), Meta::COMMENT_RATING, true)) {
            return (float)$rating;
        }
        return 0;
    }


    /**
     * - Является ли комментарий к сайту
     * @return bool 
     */
    function isSiteComment(): bool
    {
        $frontId = (int)get_option('page_on_front');
        return $this->getPostId() === $frontId;
    }
}
