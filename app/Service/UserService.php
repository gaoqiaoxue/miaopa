<?php

namespace App\Service;

use App\Exception\LogicException;
use App\Library\Contract\AuthTokenInterface;
use Hyperf\DbConnection\Db;
use Hyperf\Di\Annotation\Inject;

class UserService
{
    #[Inject]
    protected AuthTokenInterface $authToken;

    public function login(array $data): array
    {
        $user = Db::table('user')->where(['username' => $data['username']])->first();
        if(!$user){
            throw new LogicException('账号不存在');
        }
        if(!checkPassword( $data['password'], $user->password)){
            throw new LogicException('账号或密码错误');
        }
        $user_data = [
            'uid' => $user->id,
            'username' => $user->username,
        ];
        $token = $this->authToken->createToken($user_data);
        return [
            'user' => $user,
            'token' => $token,
        ];
    }

    public function refreshToken():array
    {
        return $this->authToken->refreshToken();
    }

    public function logout()
    {
        if(!$this->authToken->logout()){
            throw new LogicException('退出失败');
        }
        return true;
    }
}