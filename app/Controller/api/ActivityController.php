<?php

namespace App\Controller\api;

use App\Controller\AbstractController;
use App\Library\Contract\AuthTokenInterface;
use App\Middleware\ApiMiddleware;
use App\Request\ActivityRequest;
use App\Service\ActivityService;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\AutoController;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\Validation\Annotation\Scene;

#[AutoController]
class ActivityController extends AbstractController
{
    #[Inject]
    protected ActivityService $service;

    public function index(): array
    {
        $city_id = $this->request->input('city_id', 0);
        return returnSuccess([
            'hot' => $this->service->getApiSelect(['city_id' => $city_id], 3),
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
    public function detail(ActivityRequest $request, AuthTokenInterface $authToken): array
    {
        $activity_id = $request->input('activity_id');
        $payload = $authToken->getUserData('default', false);
        $user_id = $payload['jwt_claims']['user_id'] ?? 0;
        $info = $this->service->getInfo((int)$activity_id, ['is_like'], ['user_id' => $user_id]);
        return returnSuccess($info);
    }

    #[Scene('change_status')]
    #[Middleware(ApiMiddleware::class)]
    public function like(ActivityRequest $request)
    {
        $params = $request->validated();
        $user_id = $this->request->getAttribute('user_id');
        $this->service->like((int)$params['activity_id'], (int)$user_id, (int)$params['status']);
        return returnSuccess();
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
        $params['user_id'] = $this->request->getAttribute('user_id');
        $list = $this->service->getSignActivityList($params);
        return returnSuccess($list);
    }
}