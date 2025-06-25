<?php

namespace App\Middleware;

use App\Exception\NoAuthException;
use App\Library\Contract\AuthTokenInterface;
use Hyperf\Context\Context;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class AdminMiddleware implements MiddlewareInterface
{
    /**
     * @param AuthTokenInterface $authToken
     */
    public function __construct(protected AuthTokenInterface $authToken)
    {
    }

    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Retrieve Request Header Payload
        $payload = $this->authToken->getUserData('admin');
        if (!$payload){
            throw new NoAuthException();
        }

        // Context override request though proxy ServerRequestInterface class
        $request = Context::override(ServerRequestInterface::class,
            fn(ServerRequestInterface $request) => $request->withAttribute('user_data', $payload)
        );

        return $handler->handle($request);
    }
}