<?php

namespace App\Exception;

/**
 * \App\Exception\NotFoundException
 */
class NotFoundException extends BaseException
{
    /**
     * @param int $code
     * @param string $message
     * @param int $statusCode
     */
    public function __construct(string $message = 'Not Found', int $code = 0, int $statusCode = 404)
    {
        parent::__construct($statusCode, $message, $code);
    }
}