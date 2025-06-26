<?php

namespace App\Controller\api;

use App\Controller\AbstractController;
use App\Request\UserRequest;
use App\Service\UserService;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\AutoController;
use Hyperf\Validation\Annotation\Scene;

#[AutoController]
class LoginController extends AbstractController
{
    #[Inject]
    protected UserService $userService;

    /**
     *  账号密码登录
     * @param UserRequest $request
     * @return array
     */
    #[Scene('login')]
    public function login(UserRequest $request)
    {
        $data = $request->all();
        $result = $this->userService->login($data);
        return returnSuccess($result);
    }

    /**
     * 刷新用户token
     * @return array
     */
    public function refreshToken()
    {
        $new_token = $this->userService->refreshToken();
        return returnSuccess($new_token);
    }

    /**
     * 退出登录
     * @return array
     */
    public function logout()
    {
        $this->userService->logout();
        return returnSuccess();
    }
}