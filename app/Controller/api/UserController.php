<?php

namespace App\Controller\api;

use App\Controller\AbstractController;
use App\Library\Contract\AuthTokenInterface;
use App\Middleware\ApiMiddleware;
use App\Service\UserService;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\AutoController;
use Hyperf\HttpServer\Annotation\Middleware;

#[AutoController]
class UserController extends AbstractController
{
    #[Inject]
    protected UserService $service;

    public function search(AuthTokenInterface $authToken): array
    {
        $keyword = $this->request->input('keyword');
        $payload = $authToken->getUserData('default', false);
        $user_id = $payload['jwt_claims']['user_id'] ?? 0;
        $result = $this->service->getApiList(['keyword' => $keyword, 'current_user_id' => $user_id]);
        return returnSuccess($result);
    }

    #[Middleware(ApiMiddleware::class)]
    public function myProfile()
    {
//        $payload = $authToken->getUserData('default', false);
//        $user_id = $payload['jwt_claims']['user_id'] ?? 0;
//        if(empty($user_id)){
//            return returnSuccess([],'用户未登录');
//        }
        $user_id = $this->request->getAttribute('user_id');
        $user = $this->service->getInfo($user_id);
        return returnSuccess($user);
    }

    #[Middleware(ApiMiddleware::class)]
    public function changInfo()
    {
        $params = $this->request->all();
        $user_id = $this->request->getAttribute('user_id');
        $res = $this->service->changInfo($user_id, $params);
        if ($res['code'] == 0) {
            return returnError($res['msg']);
        }
        return returnSuccess([],$res['msg']);
    }

    public function userHomePage(AuthTokenInterface $authToken)
    {
        $user_id = $this->request->input('user_id');
        if (empty($user_id)) {
            return returnError('参数错误');
        }
        $payload = $authToken->getUserData('default', false);
        $current_user_id = $payload['jwt_claims']['user_id'] ?? 0;
        $info = $this->service->getInfo($user_id, ['created_days', 'prestige', 'is_follow'], ['current_user_id' => $current_user_id]);
        if (!empty($current_user_id) && $current_user_id != $user_id) {
            $this->service->addHomeViewRecord($current_user_id, $user_id);
        }
        return returnSuccess($info);
    }

    #[Middleware(ApiMiddleware::class)]
    public function visitorList()
    {
        $user_id = $this->request->getAttribute('user_id');
        $params = $this->request->all();
        $list = $this->service->getVisitorList($user_id, $params);
        return returnSuccess($list);
    }
}