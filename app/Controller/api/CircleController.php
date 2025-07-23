<?php

namespace App\Controller\api;

use App\Controller\AbstractController;
use App\Library\Contract\AuthTokenInterface;
use App\Middleware\ApiMiddleware;
use App\Request\CircleRequest;
use App\Service\CircleService;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\AutoController;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\Validation\Annotation\Scene;

#[AutoController]
class CircleController extends AbstractController
{
    #[Inject]
    protected CircleService $service;

    /**
     * 全部圈子(按分类分组,带搜索)
     * @param AuthTokenInterface $authToken
     * @return array
     */
    public function getListByType(AuthTokenInterface $authToken): array
    {
        $keyword = $this->request->input('keyword', '');
        $keyword = trim((string)$keyword);
        $payload = $authToken->getUserData('default', false);
        $user_id = $payload['jwt_claims']['user_id'] ?? 0;
        $list = $this->service->getAllByType($user_id, $keyword);
        return returnSuccess($list);
    }

    public function searchRecommend()
    {
        $list = $this->service->getAllByType(0, '',false, 10);
        return returnSuccess($list);
    }

    public function getList(AuthTokenInterface $authToken): array
    {
        $params = $this->request->all();
        $payload = $authToken->getUserData('default', false);
        $user_id = $payload['jwt_claims']['user_id'] ?? 0;
        $params['user_id'] = $user_id;
        $params['status'] = 1;
        if(!empty($params['keyword'])){
            $params['name'] = trim($params['keyword']);
        }
        $list = $this->service->getList($params, ['is_follow']);
        return returnSuccess($list);
    }

    /**
     * 首页推荐圈子
     * @param AuthTokenInterface $authToken
     * @return array
     */
    public function getRecommendLists(AuthTokenInterface $authToken): array
    {
        $payload = $authToken->getUserData('default', false);
        $user_id = $payload['jwt_claims']['user_id'] ?? 0;
        $list = $this->service->getRecommendList($user_id);
        return returnSuccess($list);
    }

    /**
     * 圈子关联IP/角色
     * @param CircleRequest $request
     * @return array
     */
    #[Scene('circle_id')]
    public function relations(CircleRequest $request): array
    {
        $circle_id = $request->input('circle_id', 0);
        $list = $this->service->getRelationsById($circle_id);
        return returnSuccess($list);
    }

    /**
     * 圈子详情
     * @param CircleRequest $request
     * @return array
     */
    #[Scene('circle_id')]
    public function detail(CircleRequest $request, AuthTokenInterface $authToken): array
    {
        $payload = $authToken->getUserData('default', false);
        $user_id = $payload['jwt_claims']['user_id'] ?? 0;
        $circle_id = $request->input('circle_id', 0);
        $list = $this->service->getInfo($circle_id, ['is_follow'], ['user_id' => $user_id]);
        return returnSuccess($list);
    }

    #[Scene('follow')]
    #[Middleware(ApiMiddleware::class)]
    public function follow(CircleRequest $request)
    {
        $user_id = $this->request->getAttribute('user_id');
        $data = $request->validated();
        $this->service->follow($user_id, $data['circle_id'], $data['status']);
        return returnSuccess([], $data['status'] == 1 ? '关注成功' : '取消关注成功');
    }
}