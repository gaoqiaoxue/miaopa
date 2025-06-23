<?php

namespace App\Library;

use App\Library\Contract\AuthTokenInterface;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Contract\RequestInterface;
use Phper666\JWTAuth\JWT;
use Phper666\JWTAuth\Util\JWTUtil;

class JwtAuthTokenLib implements AuthTokenInterface
{
    #[Inject]
    protected JWT $jwt;

    #[Inject]
    protected RequestInterface $request;

    /**
     * @param $userData
     * @param string $scene
     * @return array
     */
    public function createToken($userData, string $scene = 'default'): array
    {
        try {

            $token = $this->jwt->getToken($scene, $userData);
            return [
                'token' => $token->toString(),
                'exp' => $this->jwt->getTTL($token->toString()),
            ];
        } catch (\Exception $e) {
            // 异常直接接管
            // TODO: 处理自定义异常
            return [];
        }
    }

    public function refreshToken(): array
    {
        try {
            $old_token = JWTUtil::getToken($this->request);
            $token = $this->jwt->refreshToken($old_token);
            return [
                'token' => $token->toString(),
                'exp' => $this->jwt->getTTL($token->toString()),
            ];
        } catch (\Exception $e) {
            // 异常直接接管
            // TODO: 处理自定义异常
            return [];
        }
    }

    public function logout(): bool
    {
        try {
            $token = JWTUtil::getToken($this->request);
            return $this->jwt->logout($token);
        } catch (\Exception $e) {
            // 异常直接接管
            // TODO: 处理自定义异常
            return false;
        }
    }

    public function getUserData(string $scene = 'default'): array
    {
        try {
            $token = JWTUtil::getToken($this->request);
            if ($token !== false && $this->jwt->verifyTokenAndScene($scene, $token)) {
                return [
                    'dynamic_exp' => $this->jwt->getTokenDynamicCacheTime($token),
                    'jwt_claims' => JWTUtil::getParserData($this->request)
                ];
            }
        } catch (\Exception $e) {
            // 异常直接接管
            // TODO: 处理自定义异常
            return [];
        }
        return [];
    }
}