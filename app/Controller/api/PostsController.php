<?php

namespace App\Controller\api;

use App\Constants\AuditStatus;
use App\Controller\AbstractController;
use App\Library\Contract\AuthTokenInterface;
use App\Middleware\ApiMiddleware;
use App\Request\PostsRequest;
use App\Service\PostsService;
use App\Service\UserFollowService;
use App\Service\UserViewRecordService;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\AutoController;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\Validation\Annotation\Scene;

#[AutoController]
class PostsController extends AbstractController
{
    #[Inject]
    protected PostsService $service;

    public function getList(): array
    {
        $params = $this->request->all();
        $list = $this->service->getApiList($params);
        return returnSuccess($list);
    }

    #[Middleware(ApiMiddleware::class)]
    public function getMyPosts(): array
    {
        $params = $this->request->all();
        $user_id = $this->request->getAttribute('user_id');
        $params['user_id'] = $user_id;
        $list = $this->service->getApiList($params);
        return returnSuccess($list);
    }

    public function getUserPosts(): array
    {
        $params = $this->request->all();
        if (empty($params['user_id'])) {
            return returnError('缺少必要参数user_id');
        }
        $params['audit_status'] = AuditStatus::PASSED->value;
        $params['del_flag'] = 0;
        $list = $this->service->getApiList($params);
        return returnSuccess($list);
    }

    #[Scene('id')]
    public function detail(
        PostsRequest          $request,
        AuthTokenInterface    $authToken,
        UserFollowService     $followService,
        UserViewRecordService $viewService
    ): array
    {
        $post_id = $request->input('post_id');
        $payload = $authToken->getUserData('default', false);
        $user_id = $payload['jwt_claims']['user_id'] ?? 0;
        $info = $this->service->getInfo($post_id, ['is_like'], $user_id);
        if (!empty($user_id) && $user_id != $info->user_id) {
            $viewService->addPostViewRecord($info->post_type, $user_id, $post_id);
            $info->is_follow = $followService->checkIsFollow($user_id, $info->user_id);
        } else {
            $info->is_follow = 0;
        }
        return returnSuccess($info);
    }

    #[Scene('publish')]
    #[Middleware(ApiMiddleware::class)]
    public function publish(PostsRequest $request): array
    {
        $params = $request->validated();
        $user_id = $this->request->getAttribute('user_id');
        $res = $this->service->publish($user_id, $params);
        return returnSuccess(['id' => $res]);
    }
}