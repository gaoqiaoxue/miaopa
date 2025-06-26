<?php

namespace App\Controller\admin;

use App\Controller\AbstractController;
use App\Request\SysUserRequest;
use App\Service\SysUserService;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\AutoController;
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
    public function refreshToken()
    {
        $new_token = $this->service->refreshToken();
        return returnSuccess($new_token);
    }

    /**
     * 退出登录
     * @return array
     */
    public function logout()
    {
        $this->service->logout();
        return returnSuccess();
    }

}