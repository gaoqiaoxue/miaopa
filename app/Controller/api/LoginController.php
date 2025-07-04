<?php

namespace App\Controller\api;

use App\Controller\AbstractController;
use App\Request\UserRequest;
use App\Service\UserLoginService;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\AutoController;
use Hyperf\Validation\Annotation\Scene;

#[AutoController]
class LoginController extends AbstractController
{
    #[Inject]
    protected UserLoginService $service;

    /**
     *  账号密码登录
     * @param UserRequest $request
     * @return array
     */
    #[Scene('login')]
    public function login(UserRequest $request)
    {
        $data = $request->all();
        $result = $this->service->login($data);
        return returnSuccess($result);
    }

    #[Scene('wechat_auth')]
    public function wechatMiniAuth(UserRequest $request)
    {
        $data = $request->all();
        $result = $this->service->wechatMiniAuth($data['code']);
        if(isset($result['token'])){
            return returnSuccess($result);
        }else{
            return returnSuccess($result,'请绑定手机号', 100);
        }
    }

    #[Scene('wechat_bind')]
    public function wechatGetPhoneNumber(UserRequest $request)
    {
        $data = $request->all();
        $result = $this->service->wechatMiniGetPhoneNumber($data['code'], $data['core_id']);
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