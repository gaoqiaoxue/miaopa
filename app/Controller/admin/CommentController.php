<?php

namespace App\Controller\admin;

use App\Controller\AbstractController;
use App\Middleware\AdminMiddleware;
use App\Request\CommentRequest;
use App\Service\CommentService;
use App\Service\PostsService;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\AutoController;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\Validation\Annotation\Scene;

#[AutoController]
#[Middleware(AdminMiddleware::class)]
class CommentController extends AbstractController
{
    #[Inject]
    protected CommentService $service;

    public function getList(): array
    {
        $params = $this->request->all();
        $list = $this->service->getList($params);
        return returnSuccess($list);
    }

    #[Scene('id')]
    public function getInfo(CommentRequest $request, PostsService $postsService): array
    {
        $comment_id = $request->input('comment_id', 0);
        $comment = $this->service->getInfo($comment_id);
        $comment->post = $postsService->getInfo($comment->post_id);
        return returnSuccess($comment);
    }

    #[Scene('id')]
    public function delete(CommentRequest $request): array
    {
        $comment_id = $request->input('comment_id', 0);
        $this->service->delete($comment_id);
        return returnSuccess();
    }

    #[Scene('set_top')]
    public function setTop(CommentRequest $request): array
    {
        $params = $request->validated();
        $this->service->setTop($params['comment_id'], $params['is_top'] ?? 1);
        return returnSuccess();
    }
}