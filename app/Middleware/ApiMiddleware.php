<?php

namespace App\Middleware;

use App\Exception\NoAuthException;
use App\Library\Contract\AuthTokenInterface;
use App\Service\UserService;
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
    public function __construct(protected AuthTokenInterface $authToken, protected UserService $userService)
    {
    }

    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $userData = $request->getAttribute('user_data');
        if (empty($userData)) {
            $payload = $this->authToken->getUserData();
            if (!$payload) {
                throw new NoAuthException();
            }
            $userData = $payload['jwt_claims'];
        }
        $user = $this->userService->getAuthUserInfo($userData['user_id']);
        if(empty($user)){
            throw new NoAuthException('用户不存在', 0, 402);
        }
        $request = $request->withAttribute('user_id', $userData['user_id'])
            ->withAttribute('user_data', $userData);
        $request = Context::override(ServerRequestInterface::class, fn() => $request);
        return $handler->handle($request);
    }
}