<?php

namespace App\Controller\api;

use App\Controller\AbstractController;
use App\Library\Contract\AuthTokenInterface;
use Hyperf\DbConnection\Db;
use Hyperf\HttpServer\Annotation\AutoController;

#[AutoController]
class LoginController extends AbstractController
{
    public function login(AuthTokenInterface $authToken)
    {
        $username = $this->request->input('username');
        $password = $this->request->input('password');
        $user = Db::table('user')->where(['username' => $username])->first();
        if(!$user){
            return returnError('账号不存在');
        }
        if(!checkPassword($password, $user->password)){
            return returnError('账号或密码错误');
        }
        $user_data = [
            'uid' => $user->id,
            'username' => $user->username,
        ];
        $token = $authToken->createToken($user_data);
        return returnSuccess([
            'user' => $user,
            'token' => $token,
        ]);
    }

    public function refreshToken(AuthTokenInterface $authToken)
    {
        $new_token = $authToken->refreshToken();
        return returnSuccess($new_token);
    }

    public function logout(AuthTokenInterface $authToken)
    {
        $res = $authToken->logout();
        if($res){
            return returnSuccess([],'已退出');
        }
        return returnError('操作失败');
    }
}