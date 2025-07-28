<?php

namespace App\Controller\api;

use App\Controller\AbstractController;
use App\Middleware\ApiBaseMiddleware;
use App\Middleware\ApiMiddleware;
use App\Request\UserFollowRequest;
use App\Service\UserFollowService;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\AutoController;
use Hyperf\HttpServer\Annotation\Middleware;

#[AutoController]
#[Middleware(ApiBaseMiddleware::class)]
class FollowController extends AbstractController
{

    #[Inject]
    protected UserFollowService $service;

    #[Middleware(ApiMiddleware::class)]
    public function follow(UserFollowRequest $request): array
    {
        $params = $request->validated();
        $user_id = $this->request->getAttribute('user_id');
        $this->service->follow($user_id, $params['follow_id'], $params['status']);
        return returnSuccess([], $params['status'] == 1 ? '关注成功' : '取消关注成功');
    }

    #[Middleware(ApiMiddleware::class)]
    public function myFollowList()
    {
        $user_id = $this->request->getAttribute('user_id');
        $params = $this->request->all();
        $params['user_id'] = $user_id;
        $params['current_user_id'] = $user_id;
        $list = $this->service->getFollowList($params);
        return returnSuccess($list);
    }

    #[Middleware(ApiMiddleware::class)]
    public function myFansList()
    {
        $user_id = $this->request->getAttribute('user_id');
        $params = $this->request->all();
        $params['user_id'] = $user_id;
        $params['current_user_id'] = $user_id;
        $list = $this->service->getFansList($params);
        return returnSuccess($list);
    }

    public function userFollowList()
    {
        $params = $this->request->all();
        if (empty($params['user_id'])) {
            return returnError('请选择用户');
        }
        $params['current_user_id'] = $this->request->getAttribute('user_id', 0);
        $list = $this->service->getFollowList($params);
        return returnSuccess($list);
    }

    public function userFansList()
    {
        $params = $this->request->all();
        if (empty($params['user_id'])) {
            return returnError('请选择用户');
        }
        $params['current_user_id'] = $this->request->getAttribute('user_id', 0);
        $list = $this->service->getFansList($params);
        return returnSuccess($list);
    }
}