<?php

namespace Vnet\Ajax;

use Vnet\Constants\Meta;
use Vnet\Contact\Telegram;
use Vnet\Entities\PostTour;
use Vnet\Theme\B24;
use Vnet\Theme\Template;

class Comments extends Core
{


    /**
     * - Загрузка ajax комментариев
     */
    function loadComments()
    {
        $postId = (int)($_REQUEST['post_id'] ?? 0);
        $page = (int)($_REQUEST['page'] ?? 1);

        Template::theTemplate('comments-list', [
            'post_id' => $postId,
            'page' => $page
        ]);

        exit;
    }


    /**
     * - Добавляет новый комментарий
     */
    function addComment()
    {
        $this->validate([
            'captcha' => true,
            'validate' => [
                'avatar' => 'required',
                'name' => 'required|min:3|max:50',
                'rating' => 'required|numeric|min:1|max:5',
                'comment' => 'required|min:10|max:500',
                'post_id' => 'required|numeric|min:1'
            ]
        ]);

        $commentData = [
            'comment_author' => $_REQUEST['name'],
            'comment_content' => $_REQUEST['comment'],
            'comment_post_ID' => $_REQUEST['post_id']
        ];

        $commentMeta = [
            Meta::COMMENT_AVATAR => $_REQUEST['avatar'],
            Meta::COMMENT_RATING => $_REQUEST['rating']
        ];

        $comment = \Vnet\Theme\Comments::addComment($commentData, $commentMeta);

        if (!$comment) {
            $this->theError();
        }

        $adminMsg = 'Новый отзыв';
        $adminMsg .= PHP_EOL . 'от: ' . $comment->getAuthorName();
        $adminMsg .= PHP_EOL . 'рейтинг: ' . $comment->getRating();

        if (!$comment->isSiteComment()) {
            $adminMsg .= PHP_EOL . 'к туру: ' . PostTour::getById($comment->getPostId())->getTitle();
        }

        $adminMsg .= PHP_EOL . $comment->getContent();

        B24::createNotif($adminMsg);

        $this->theSuccess([
            'clearInputs' => true,
            'msg' => 'commentModeration'
        ]);
    }
}
