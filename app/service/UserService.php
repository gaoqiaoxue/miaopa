<?php

namespace App\service;

use App\Exception\LogicException;
use Hyperf\DbConnection\Db;

class UserService
{
    public function login($data, $authToken)
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
        $token = $authToken->createToken($user_data);
        return [
            'user' => $user,
            'token' => $token,
        ];
    }
}