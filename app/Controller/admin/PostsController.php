<?php

namespace App\Controller\admin;

use App\Constants\AuditStatus;
use App\Controller\AbstractController;
use App\Middleware\AdminMiddleware;
use App\Request\PostsRequest;
use App\Service\PostsService;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\AutoController;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\Validation\Annotation\Scene;

#[AutoController()]
#[Middleware(AdminMiddleware::class)]
class PostsController extends AbstractController
{
    #[Inject]
    protected PostsService $service;

    public function getList(): array
    {
        $params = $this->request->all();
        $params['audit_status'] = AuditStatus::PASSED->value;
        $list = $this->service->getList($params);
        return returnSuccess($list);
    }

    public function getAuditList(): array
    {
        $params = $this->request->all();
        $params['source'] = 'user';
        $list = $this->service->getList($params);
        return returnSuccess($list);
    }

    #[Scene('id')]
    public function getInfo(PostsRequest $request): array
    {
        $post_id = $request->input('post_id', 0);
        $post = $this->service->getInfo($post_id);
        return returnSuccess($post);
    }

    #[Scene('id')]
    public function delete(PostsRequest $request): array
    {
        $post_id = $request->input('post_id', 0);
        $this->service->delete($post_id);
        return returnSuccess();
    }

    #[Scene('id')]
    public function pass(PostsRequest $request)
    {
        $post_id = $request->input('post_id');
        $cur_user_id = $this->request->getAttribute('user_id');
        $this->service->pass($post_id, $cur_user_id);
        return returnSuccess();
    }

    #[Scene('id')]
    public function reject(PostsRequest $request)
    {
        $params =  $request->all();
        $cur_user_id = $this->request->getAttribute('user_id');
        $this->service->reject($params['post_id'], $cur_user_id, (string) $params['reject_reason'] ??'');
        return returnSuccess();
    }
}