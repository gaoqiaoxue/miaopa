<?php

namespace App\Exception;

use Throwable;

/**
 * \App\Exception\ParametersException
 */
class ParametersException extends BaseException
{
    /**
     * @param int $code
     * @param string $message
     * @param int $statusCode
     */
    public function __construct(string $message = '参数错误', int $code = 500, int $statusCode = 200)
    {
        parent::__construct($statusCode, $message, $code);
    }
}