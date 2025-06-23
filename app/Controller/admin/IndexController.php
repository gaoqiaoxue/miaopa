<?php

namespace App\Controller\admin;

use App\Controller\AbstractController;
use App\Middleware\AdminMiddleware;
use Hyperf\DbConnection\Db;
use Hyperf\HttpServer\Annotation\AutoController;
use Hyperf\HttpServer\Annotation\Middleware;

#[AutoController]
#[Middleware(AdminMiddleware::class)]
class IndexController extends AbstractController
{
    public function index(){
        $user_data = $this->request->getAttribute("user_data");
        return [
            'data' => $user_data,
        ];
    }

    /**
     * 我的账号资料
     * @return array
     */
    public function profile()
    {
        $user_data = $this->request->getAttribute("user_data");
        $user_id = $user_data['user_id'];
        $user = Db::table('sys_user')->where(['user_id' => $user_id])->first();
        if(!$user){
            return returnError('用户不存在');
        }
        return returnSuccess($user);
    }

    /**
     * 重置个人密码
     * @return array
     */
    public function changePassword()
    {
        $old_password = $this->request->getAttribute("old_password");
        $new_password = $this->request->input("new_password");
        if(empty($new_password)){
            return returnError('请输入新密码');
        }
        $user_data = $this->request->getAttribute("user_data");
        $user_id = $user_data['user_id'];
        $user = Db::table('sys_user')->where(['user_id' => $user_id])->first();
        if(!$user){
            return returnError('用户不存在');
        }
        if(!checkPassword($old_password, $user->password)){
            return returnError('原始密码输入错误');
        }
        $res = Db::table('sys_user')
            ->where(['user_id' => $user_id])
            ->update(['password' => setPassword($new_password)]);
        if($res){
            return returnSuccess('设置成功');
        }
        return returnError('设置失败');
    }


}