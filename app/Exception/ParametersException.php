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
    public function __construct(string $message = 'Bad Request', int $code = 400, int $statusCode = 400)
    {
        parent::__construct($statusCode, $message, $code);
    }
}