<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

namespace App\Exception\Handler;

use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\ExceptionHandler\ExceptionHandler;
use Hyperf\HttpMessage\Stream\SwooleStream;
use Hyperf\Validation\ValidationException;
use Psr\Http\Message\ResponseInterface;
use Throwable;
use Hyperf\Logger\LoggerFactory;
use Psr\Container\ContainerInterface;

class AppExceptionHandler extends ExceptionHandler
{
    protected $logger;

    public function __construct(ContainerInterface $container)
    {
        $this->logger = $container->get(LoggerFactory::class)->get('error');
    }

//    public function __construct(protected StdoutLoggerInterface $logger)
//    {
//    }

    public function handle(Throwable $throwable, ResponseInterface $response)
    {
        if($throwable instanceof ValidationException){
            $this->logger->error('Validation failed', [
                'errors' => $throwable->validator->errors()->all(),
                'input' => $throwable->validator->getData(),
            ]);
        }else{
            $this->logger->error(sprintf('%s[%s] in %s', $throwable->getMessage(), $throwable->getLine(), $throwable->getFile()));
        }
        $this->logger->error($throwable->getTraceAsString());
        return $response->withHeader('Server', 'Hyperf')->withStatus(500)->withBody(new SwooleStream(
            json_encode([
                'code' => $throwable->getCode(),
                'msg' => 'Internal Server Error.'
            ])
        ));
    }

    public function isValid(Throwable $throwable): bool
    {
        return true;
    }
}
