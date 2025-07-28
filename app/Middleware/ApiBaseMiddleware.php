<?php

namespace App\Middleware;

use App\Library\Contract\AuthTokenInterface;
use App\Service\UserStaticsService;
use Hyperf\Context\Context;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ApiBaseMiddleware implements MiddlewareInterface
{
    /**
     * @param AuthTokenInterface $authToken
     */
    public function __construct(protected AuthTokenInterface $authToken, protected UserStaticsService $userStaticsService)
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
        $payload = $this->authToken->getUserData('default', false);
        if (!empty($payload)) {
            $user_data = $payload['jwt_claims'];
            $request = Context::override(ServerRequestInterface::class, function (ServerRequestInterface $request) use ($user_data) {
                return $request->withAttribute('user_id', $user_data['user_id'])
                    ->withAttribute('user_data', $user_data);
            });
            !empty($user_data['user_id']) && $this->userStaticsService->recordActive('user', $user_data['user_id']);
        }else{
            $core_id = $request->getHeaderLine('core_id');
            !empty($core_id) && $this->userStaticsService->recordActive('guest', $core_id);
        }
        return $handler->handle($request);
    }
}