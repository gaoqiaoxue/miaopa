<?php

namespace App\Controller\admin;

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

}