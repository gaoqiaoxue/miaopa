<?php
namespace App\Exception\Handler;
use Hyperf\ExceptionHandler\ExceptionHandler;
use Hyperf\HttpMessage\Stream\SwooleStream;
use Hyperf\Validation\ValidationException;
use Swow\Psr7\Message\ResponsePlusInterface;
use Throwable;

class ValidationExceptionHandler extends ExceptionHandler
{
    public function handle(Throwable $throwable, ResponsePlusInterface $response)
    {
        $this->stopPropagation();
        $body = $throwable->validator->errors()->first();
        return $response->withHeader("Content-Type", "application/json")
            ->withStatus(200)
            ->withBody(new SwooleStream(json_encode([
                'code' => 500,
                'msg' => $body
            ])));
    }

    public function isValid(Throwable $throwable): bool
    {
        return $throwable instanceof ValidationException;
    }

}
