<?php

namespace App\Middleware;

use App\Exception\NoAuthException;
use App\Library\Contract\AuthTokenInterface;
use Hyperf\Context\Context;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ApiMiddleware implements MiddlewareInterface
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
        $payload = $this->authToken->getUserData();
        if (!$payload){
            throw new NoAuthException();
        }
        $user_data = $payload['jwt_claims'];
        // Context override request though proxy ServerRequestInterface class
//        $request = Context::override(ServerRequestInterface::class,
//            fn(ServerRequestInterface $request) => $request->withAttribute('user_data', $payload)
//        );
        $request = Context::override(ServerRequestInterface::class, function (ServerRequestInterface $request) use ($user_data) {
            return $request->withAttribute('user_id', $user_data['user_id'])
                ->withAttribute('user_data', $user_data);
        });

        return $handler->handle($request);
    }
}