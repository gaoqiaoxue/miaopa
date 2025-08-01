<?php

namespace App\Controller\api;

use App\Controller\AbstractController;
use App\Middleware\ApiBaseMiddleware;
use App\Middleware\ApiMiddleware;
use App\Request\ActivityRequest;
use App\Service\ActivityService;
use App\Service\UserViewRecordService;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\AutoController;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\Validation\Annotation\Scene;

#[AutoController]
#[Middleware(ApiBaseMiddleware::class)]
class ActivityController extends AbstractController
{
    #[Inject]
    protected ActivityService $service;

    // 热门活动和有活动的日期
    public function index(): array
    {
        $city_id = $this->request->input('city_id', 0);
        return returnSuccess([
            'hot' => $this->service->getApiList(['city_id' => $city_id], false, 10),
            'dates' => $this->service->getDates((int)$city_id),
        ]);
    }

    public function getList(): array
    {
        $params = $this->request->all();
        $list = $this->service->getApiList($params);
        return returnSuccess($list);
    }

    #[Scene('id')]
    public function detail(
        ActivityRequest       $request,
        UserViewRecordService $viewService
    ): array
    {
        $activity_id = $request->input('activity_id');
        $user_id = $this->request->getAttribute('user_id', 0);
        $info = $this->service->getInfo((int)$activity_id, ['is_like', 'is_sign'], ['user_id' => $user_id]);
        if (!empty($user_id)) {
            $viewService->addViewRecord('activity', $user_id, $activity_id);
        }
        return returnSuccess($info);
    }

    #[Scene('change_status')]
    #[Middleware(ApiMiddleware::class)]
    public function like(ActivityRequest $request)
    {
        $params = $request->validated();
        $user_id = $this->request->getAttribute('user_id');
        $this->service->like((int)$params['activity_id'], (int)$user_id, (int)$params['status']);
        return returnSuccess([], $params['status'] ? '收藏成功' : '已取消');
    }

    #[Middleware(ApiMiddleware::class)]
    public function likeList()
    {
        $params = $this->request->all();
        $user_id = $this->request->getAttribute('user_id');
        $list = $this->service->likeList($user_id, $params);
        return returnSuccess($list);
    }

    #[Scene('id')]
    #[Middleware(ApiMiddleware::class)]
    public function signUp(ActivityRequest $request): array
    {
        $activity_id = $request->input('activity_id');
        $user_id = $this->request->getAttribute('user_id');
        $this->service->signUp((int)$activity_id, (int)$user_id);
        return returnSuccess();
    }

    #[Middleware(ApiMiddleware::class)]
    public function signList()
    {
        $params = $this->request->all();
        $user_id = $this->request->getAttribute('user_id');
        $list = $this->service->getSignActivityList($user_id, $params);
        return returnSuccess($list);
    }

    #[Middleware(ApiMiddleware::class)]
    public function history(): array
    {
        $params = $this->request->all();
        $user_id = $this->request->getAttribute('user_id');
        $list = $this->service->viewList($user_id, $params);
        return returnSuccess($list);
    }
}