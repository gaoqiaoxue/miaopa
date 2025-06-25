<?php

namespace App\Exception\Handler;

use App\Exception\BaseException;
use Hyperf\ExceptionHandler\ExceptionHandler;
use Hyperf\HttpMessage\Stream\SwooleStream;
use Psr\Http\Message\ResponseInterface;
use Throwable;

/**
 * \App\Exception\Handler\ApiExceptionHandler
 */
class ApiExceptionHandler extends ExceptionHandler
{
    /**
     * @param Throwable $throwable
     * @param ResponseInterface $response
     * @return \Psr\Http\Message\MessageInterface|ResponseInterface
     */
    public function handle(Throwable $throwable, ResponseInterface $response)
    {
        $this->stopPropagation();

        return $response->withHeader("Content-Type", "application/json")
            ->withStatus($throwable->getStatusCode())
            ->withBody(new SwooleStream(json_encode([
                'code' => $throwable->getCode(),
                'msg' => $throwable->getMessage()
            ])));
    }

    /**
     * @param Throwable $throwable
     * @return bool
     */
    public function isValid(Throwable $throwable): bool
    {
        return $throwable instanceof BaseException;
    }
}