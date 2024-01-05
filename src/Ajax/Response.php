<?php

namespace Vnetby\Wptheme\Ajax;

use stdClass;
use Vnetby\Wptheme\Container;

class Response
{
    const STATUS_ERROR = 'error';
    const STATUS_SUCCESS = 'success';

    protected int $code = 0;

    /**
     * @var string<static::STATUS_*>
     */
    public string $status = '';

    public ?string $msg = null;

    public ?string $redirect = null;

    public bool $reload = false;

    public ?array $errorFields = null;

    public ?array $data = null;


    /**
     * @param array{
     *      status: string<self::STATUS_*>,
     *      msg: ?string,
     *      redirect: ?string,
     *      reload: ?bool,
     *      errorFields: ?array<string,string>,
     *      data: ?array<string,mixed>
     * } $data
     * @param integer $code
     */
    protected function __construct(array $data, int $code = 0)
    {
        $this->code = $code;
        $this->status = $data['status'];
        $this->msg = $data['msg'] ?? null;
        $this->redirect = $data['redirect'] ?? null;
        $this->reload = !empty($data['reload']);
        $this->errorFields = $data['errorFields'] ?? null;
        $this->data = $data['data'] ?? null;
    }


    /**
     * - Отправляет успешный ответ
     * @param array|string $data либо сообщение, либо массив данных для конструктора класса
     * @param integer $code
     */
    static function theSuccess($data, $code = 200)
    {
        $resData = static::formatResData($data);
        $resData['status'] = static::STATUS_SUCCESS;
        $res = new static($resData, $code);
        $res->response();
    }


    /**
     * - Отправляет ответ с ошибкой
     * @param array|string $data либо сообщение, либо массив данных для конструктора класса
     * @param integer $code
     */
    static function theError($data, $code = 500)
    {
        $resData = static::formatResData($data);
        $resData['status'] = static::STATUS_ERROR;
        $res = new static($resData, $code);
        $res->response();
    }


    protected static function formatResData($data): array
    {
        if (is_string($data)) {
            $res = [
                'msg' => $data
            ];
        } else {
            $res = $data;
        }

        if (isset($res['msg'])) {
            $res['msg'] = Container::getLoader()->getMessage($res['msg']);
        }

        return $res;
    }


    function response()
    {
        http_response_code($this->code);
        header('Content-Type: application/json');
        echo $this->json();
        exit;
    }


    function json(): string
    {
        return json_encode($this, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
