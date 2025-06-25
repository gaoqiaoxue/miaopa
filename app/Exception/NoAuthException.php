<?php

namespace App\Exception;


/**
 * \App\Exception\NoAuthException
 */
class NoAuthException extends BaseException
{
    /**
     * @param int $code
     * @param string $message
     * @param int $statusCode
     */
    public function __construct(string $message = '请先登录', int $code = 0, int $statusCode = 401)
    {
        parent::__construct($statusCode, $message, $code);
    }
}