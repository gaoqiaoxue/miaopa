<?php

namespace App\Controller\api;

use App\Controller\AbstractController;
use App\Library\Contract\AuthTokenInterface;
use App\Middleware\ApiMiddleware;
use App\Request\CommentRequest;
use App\Service\CommentService;
use App\Service\PostsService;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\AutoController;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\Validation\Annotation\Scene;

#[AutoController]
class CommentController extends AbstractController
{
    #[Inject]
    protected CommentService $service;

    public function getAnswerList(AuthTokenInterface $authToken)
    {
        $payload = $authToken->getUserData('default', false);
        $user_id = $payload['jwt_claims']['user_id'] ?? 0;
        $params = $this->request->all();
        if (empty($params['post_id'])) {
            return returnError('参数错误');
        }
        $list = $this->service->getCommentList((array)$params, (int)$user_id, ['is_like']);
        return returnSuccess($list);
    }

    public function getAnswerDetail(AuthTokenInterface $authToken, PostsService $postsService): array
    {
        $payload = $authToken->getUserData('default', false);
        $user_id = $payload['jwt_claims']['user_id'] ?? 0;
        $comment_id = $this->request->input('answer_id', 0);
        $detail = $this->service->getCommentDetail((int)$comment_id, (int)$user_id);
        $detail->post_info = $postsService->getInfo($detail->post_id,['is_like'], (int)$user_id);
        return returnSuccess($detail);
    }

    public function getCommentList(AuthTokenInterface $authToken): array
    {
        $payload = $authToken->getUserData('default', false);
        $user_id = $payload['jwt_claims']['user_id'] ?? 0;
        $params = $this->request->all();
        if (empty($params['answer_id']) && empty($params['post_id'])) {
            return returnError('参数错误');
        }
        $list = $this->service->getCommentList((array)$params, (int)$user_id, ['reply']);
        return returnSuccess($list);
    }

    public function getReplyList(AuthTokenInterface $authToken): array
    {
        $payload = $authToken->getUserData('default', false);
        $user_id = $payload['jwt_claims']['user_id'] ?? 0;
        $params = $this->request->all();
        if (empty($params['comment_id'])) {
            return returnError('参数错误');
        }
        $list = $this->service->getReplyList((array)$params, (int)$user_id);
        return returnSuccess($list);
    }

    #[Middleware(ApiMiddleware::class)]
    #[Scene('comment')]
    public function comment(CommentRequest $request)
    {
        $params = $request->validated();
        $user_id = $this->request->getAttribute('user_id');
        $this->service->comment($user_id, $params['post_id'], $params['content'], $params['images'] ?? []);
        return returnSuccess();
    }

    #[Middleware(ApiMiddleware::class)]
    #[Scene('reply')]
    public function reply(CommentRequest $request)
    {
        $params = $request->validated();
        $user_id = $this->request->getAttribute('user_id');
        $this->service->reply($user_id, $params['parent_id'], $params['content'], $params['images'] ?? []);
        return returnSuccess();
    }

    #[Middleware(ApiMiddleware::class)]
    #[Scene('like')]
    public function like(CommentRequest $request)
    {
        $params = $request->validated();
        $user_id = $this->request->getAttribute('user_id');
        $this->service->like($params['comment_id'], $user_id, $params['status'] ?? 1);
        return returnSuccess();
    }
}