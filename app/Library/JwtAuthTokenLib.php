<?php

namespace App\Library;

use App\Exception\NoAuthException;
use App\Exception\ParametersException;
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
        } catch (\Throwable $e) {
            throw new ParametersException($e->getMessage());
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
        } catch (\Throwable $e) {
            throw new ParametersException($e->getMessage());
        }
    }

    public function logout(): bool
    {
        try {
            $token = JWTUtil::getToken($this->request);
            return $this->jwt->logout($token);
        } catch (\Throwable $e) {
            throw new ParametersException($e->getMessage());
        }
    }

    public function getUserData(string $scene = 'default', bool $force = true): array
    {
        try {
            $token = JWTUtil::getToken($this->request);
            $status_code = $token === false ? 401 : 402;
            if ($token !== false && $this->jwt->verifyTokenAndScene($scene, $token)) {
                return [
                    'dynamic_exp' => $this->jwt->getTokenDynamicCacheTime($token),
                    'jwt_claims' => JWTUtil::getParserData($this->request)
                ];
            }
        } catch (\Throwable $e) {
            if ($force) {
                throw new NoAuthException($e->getMessage(), 0, $status_code);
            }
        }
        if ($force) {
            throw new NoAuthException('token 异常', 0, $status_code);
        }
        return [];
    }
}