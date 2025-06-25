<?php

namespace App\Controller\api;

use App\Controller\AbstractController;
use App\Library\Contract\AuthTokenInterface;
use App\Request\UserRequest;
use App\service\UserService;
use Hyperf\HttpServer\Annotation\AutoController;
use Hyperf\Validation\Annotation\Scene;

#[AutoController]
class LoginController extends AbstractController
{
    /**
     * 账号密码登录
     * @param AuthTokenInterface $authToken
     * @param UserRequest $request
     * @param UserService $userService
     * @return array
     */
    #[Scene('login')]
    public function login(AuthTokenInterface $authToken, UserRequest $request, UserService $userService)
    {
        $data = $request->all();
        $result = $userService->login($data,$authToken);
        return returnSuccess($result);
    }

    /**
     * 刷新用户token
     * @param AuthTokenInterface $authToken
     * @return array
     */
    public function refreshToken(AuthTokenInterface $authToken)
    {
        $new_token = $authToken->refreshToken();
        return returnSuccess($new_token);
    }

    /**
     * 退出登录
     * @param AuthTokenInterface $authToken
     * @return array
     */
    public function logout(AuthTokenInterface $authToken)
    {
        $res = $authToken->logout();
        if($res){
            return returnSuccess([],'已退出');
        }
        return returnError('操作失败');
    }
}