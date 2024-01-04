<?php

/**
 * - Класс для работы с валидацией форм
 */

namespace Vnet;

use Rakit\Validation\Validator as CoreValidator;


class Validator
{

    /**
     * - Валидирует google captcha
     * @return bool
     */
    static function validateCaptcha()
    {
        $secret = defined('CAPTCHA_SECRET') ? constant('CAPTCHA_SECRET') : null;
        $token = $_REQUEST['g-recaptcha-response'] ?? '';

        // капча не настроена
        if (!$secret) {
            return true;
        }

        // капча настроена, но токен не передан
        if (!$token) {
            return false;
        }

        $ch = curl_init('https://www.google.com/recaptcha/api/siteverify');

        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS => [
                'secret' => $secret,
                'response' => $token
            ]
        ]);

        $res = curl_exec($ch);
        $res = @json_decode($res, true);
        curl_close($ch);

        $score = $res['score'] ?? 0;

        return (float)$score > 0.5;
    }


    /**
     * - Валидирует массив полей
     * @see https://github.com/rakit/validation
     * @param array $arFields ассотиативный массив в которм ключ соответствует name поля
     * @return true|array true в случае успеха, array массив полей с ошибками и сообщениями
     */
    static function validateFields($arFields, $values = null)
    {
        $errors = [];

        $validation = (new CoreValidator)->make($values !== null ? $values : ($_REQUEST + $_FILES), $arFields);

        $validation->validate();

        if (!$validation->fails()) {
            return true;
        }

        $errors = $validation->errors()->toArray();

        $res = [];

        foreach ($errors as $name => $arErrors) {
            $key = array_keys($arErrors)[0];
            $rule = $arFields[$name];

            $replace = [];

            if ($key === 'max') {
                preg_match("/max:([\d]+)/", $rule, $matches);
                if (isset($matches[1])) {
                    $replace[] = $matches[1];
                }
            }

            if ($key === 'min') {
                preg_match("/min:([\d]+)/", $rule, $matches);
                if (isset($matches[1])) {
                    $replace[] = $matches[1];
                }
            }

            if ($key === 'max') {
                if (strpos($rule, 'numeric') === false) {
                    $key = 'maxLength';
                }
            }

            if ($key === 'min') {
                if (strpos($rule, 'numeric') === false) {
                    $key = 'minLength';
                }
            }

            $res[$name] = Loader::getInstance()->getMessage($key, $replace);
        }

        return $res;
    }
}
