<?php

namespace App\service;

use App\Exception\LogicException;
use App\Library\Contract\AuthTokenInterface;
use Hyperf\DbConnection\Db;

class SysUserService
{
    protected AuthTokenInterface $authToken;

    public function login(array $data): array
    {
        $user = Db::table('sys_user')->where(['user_name' => $data['user_name']])->first();
        if(!$user)
            throw new LogicException('账号不存在');
        if(!checkPassword($data['password'], $user->password))
            throw new LogicException('账号或密码错误');
        $user_data = [
            'user_id' => $user->user_id,
            'user_name' => $user->user_name,
        ];
        $token = $this->authToken->createToken($user_data, 'admin');
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
