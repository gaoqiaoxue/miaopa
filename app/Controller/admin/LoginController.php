<?php

namespace App\Controller\admin;

use App\Controller\AbstractController;
use App\Middleware\AdminMiddleware;
use App\Request\SysUserRequest;
use App\Service\SysRoleService;
use App\Service\SysUserService;
use Hyperf\DbConnection\Db;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\AutoController;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\Validation\Annotation\Scene;

#[AutoController]
class LoginController extends AbstractController
{
    #[Inject]
    protected SysUserService $service;

    /**
     * 账号密码登录
     * @param SysUserRequest $request
     * @return array
     */
    #[Scene('login')]
    public function login(SysUserRequest $request)
    {
        $data = $request->validated();
        $result = $this->service->login($data);
        return returnSuccess($result);
    }

    /**
     * 刷新用户token
     * @return array
     */
    #[Middleware(AdminMiddleware::class)]
    public function refreshToken()
    {
        $new_token = $this->service->refreshToken();
        return returnSuccess($new_token);
    }

    /**
     * 退出登录
     * @return array
     */
    #[Middleware(AdminMiddleware::class)]
    public function logout()
    {
        $this->service->logout();
        return returnSuccess();
    }



    /**
     * 获取当前登录用户的权限列表
     * @return array
     */
    #[Middleware(AdminMiddleware::class)]
    public function getMyMenu(SysRoleService $service):array
    {
        $user_data = $this->request->getAttribute("user_data");
        $role_id = $user_data['role_id'];
        $menus = $service->getRolePerms($role_id);
        return returnSuccess($menus);
    }

    /**
     * 我的账号资料
     * @return array
     */
    #[Middleware(AdminMiddleware::class)]
    public function profile()
    {
        $user_id = $this->request->getAttribute("user_id");
        $user = $this->service->getInfo($user_id);
        $user->avatar_url = getAvatar($user->avatar);
        return returnSuccess($user);
    }

    /**
     * 修改个人密码
     * @return array
     */
    #[Middleware(AdminMiddleware::class)]
    #[Scene('change_psw')]
    public function changePassword(SysUserRequest $request):array
    {
        $data = $request->validated();
        $user_id = $this->request->getAttribute("user_id");
        $res = $this->service->changePassword($user_id, $data);
        return returnSuccess($res);
    }
}