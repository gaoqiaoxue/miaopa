<?php

namespace App\Controller\api;

use App\Controller\AbstractController;
use App\Middleware\ApiBaseMiddleware;
use App\Middleware\ApiMiddleware;
use App\Request\CircleRequest;
use App\Service\CircleService;
use App\Service\CircleStaticsService;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\AutoController;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\Validation\Annotation\Scene;

#[AutoController]
#[Middleware(ApiBaseMiddleware::class)]
class CircleController extends AbstractController
{
    #[Inject]
    protected CircleService $service;

    /**
     * 全部圈子(按分类分组,带搜索)
     * @return array
     */
    public function getListByType(): array
    {
        $keyword = $this->request->input('keyword', '');
        $keyword = trim((string)$keyword);
        $user_id = $this->request->getAttribute('user_id', 0);
        $list = $this->service->getAllByType($user_id, $keyword,true, 10);
        return returnSuccess($list);
    }

    public function searchRecommend()
    {
        $list = $this->service->getAllByType(0, '',false, 12);
        return returnSuccess($list);
    }

    public function getList(): array
    {
        $params = $this->request->all();
        $user_id = $this->request->getAttribute('user_id', 0);
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
     * @return array
     */
    public function getRecommendLists(): array
    {
        $user_id = $this->request->getAttribute('user_id', 0);
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
    public function detail(CircleRequest $request, CircleStaticsService $staticsService): array
    {
        $user_id = $this->request->getAttribute('user_id', 0);
        $circle_id = $request->input('circle_id', 0);
        $list = $this->service->getInfo($circle_id, ['is_follow', 'relations'], ['user_id' => $user_id]);
        if(empty($user_id)){
            $core_id = $this->request->getHeaderLine('coreId');
            !empty($core_id) && $staticsService->incrementCoreClick($circle_id, $core_id);
        }else{
            $staticsService->incrementClick($circle_id, $user_id);
        }
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