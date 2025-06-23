<?php

namespace App\Controller\admin;

use App\Controller\AbstractController;
use App\Library\Contract\AuthTokenInterface;
use Hyperf\DbConnection\Db;
use Hyperf\HttpServer\Annotation\AutoController;

#[AutoController]
class LoginController extends AbstractController
{
    public function login(AuthTokenInterface $authToken)
    {
        $username = $this->request->input('user_name');
        $password = $this->request->input('password');
        $user = Db::table('sys_user')->where(['user_name' => $username])->first();
        if(!$user){
            return returnError('账号不存在');
        }
        Db::table('sys_user')->where(['user_name' => $username])->update(['password' => password_hash($password, PASSWORD_BCRYPT)]);
        if(!checkPassword($password, $user->password)){
            return returnError('账号或密码错误');
        }
        $user_data = [
            'user_id' => $user->user_id,
            'user_name' => $user->user_name,
        ];
        $token = $authToken->createToken($user_data, 'admin');
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