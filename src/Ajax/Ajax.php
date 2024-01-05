<?php

namespace Vnetby\Wptheme\Ajax;

use Vnetby\Helpers\HelperArr;
use Vnetby\Helpers\HelperPath;
use Vnetby\Wptheme\Container;
use Vnetby\Wptheme\Traits\Singletone;

abstract class Ajax
{
    use Singletone;


    static function isAjax(): bool
    {
        return defined('VNET_DOING_AJAX');
    }


    /**
     * - Получает ссылку на ajax запрос
     * @param string $action [optional]
     * @param array $getParams [optional]
     * 
     * @return string
     */
    function getUrl($action = '', $getParams = []): string
    {
        if (!method_exists($this, $action)) {
            $name = get_called_class();
            throw new \Error("Method {$action} does not exist in class {$name}");
        }

        $url = Container::getLoader()->getAjaxUrl();
        $class = base64_encode(get_called_class());
        $method = base64_encode($action);

        // прямая ссылка на файл
        if (preg_match("%\.php$%", $url)) {
            $getParams['_class'] = $class;
            $getParams['_method'] = $method;
        } else {
            // ссылка с перезаписью
            // предполагается что в правилах перезаписи будут подставлены соответствующие гет параметры
            $url = HelperPath::join($url, $class, $method);
        }

        if ($getParams) {
            $url .= (preg_match("%\?.*%", $url) ? '&' : '?') . http_build_query($getParams);
        }

        return $url;
    }


    protected function getRequest(string $key, $def = null)
    {
        return HelperArr::getRequest($key, $def);
    }


    /**
     * - Валидирует запрос
     * - Отдаст ответ и прекратит выполнение скрипта в случае ошибки
     * @param array $sets 
     * @return void 
     */
    protected function validate(array $sets)
    {
        // только авторизованные пользователь могут выполнять ajax запрос
        if (!empty($sets['protected']) && !is_user_logged_in()) {
            http_response_code(401);
            exit;
        }

        // валидация капчи
        if (!empty($sets['captcha']) && !Container::getClassValidator()::validateCaptcha()) {
            $this->theError(['clearInputs' => true, 'msg' => 'serverError'], 403);
        }

        // валидация переданных полей
        if (!empty($sets['validate'])) {
            $res = Container::getClassValidator()::validateFields($sets['validate']);
            if (is_array($res)) {
                $this->theError(['fields' => $res], 400);
            }
        }
    }


    protected function theError($res = 'serverError', $code = 500)
    {
        Container::getClassAjaxResponse()::theError($res, $code);
    }


    protected function theSuccess($res = [], $code = 200)
    {
        Container::getClassAjaxResponse()::theSuccess($res, $code);
    }
}
