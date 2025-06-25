<?php

namespace App\Exception;


/**
 * 业务逻辑异常
 * \App\Exception\LogicException
 */
class LogicException extends BaseException
{
    /**
     * @param int $code
     * @param string $message
     * @param int $statusCode
     */
    public function __construct(string $message = '操作失败', int $code = 500, int $statusCode = 200)
    {
        parent::__construct($statusCode, $message, $code);
    }
}