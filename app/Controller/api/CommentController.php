<?php

namespace App\Controller\api;

use App\Constants\ReportType;
use App\Controller\AbstractController;
use App\Middleware\ApiBaseMiddleware;
use App\Middleware\ApiMiddleware;
use App\Request\CommentRequest;
use App\Request\ReportRequest;
use App\Service\CommentService;
use App\Service\PostsService;
use App\Service\ReportService;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\AutoController;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\Validation\Annotation\Scene;

#[AutoController]
#[Middleware(ApiBaseMiddleware::class)]
class CommentController extends AbstractController
{
    #[Inject]
    protected CommentService $service;

    public function getAnswerList()
    {
        $user_id = $this->request->getAttribute('user_id', 0);
        $params = $this->request->all();
        if (empty($params['post_id'])) {
            return returnError('参数错误');
        }
        $params['answer_id'] = 0;
        $list = $this->service->getCommentList((array)$params, (int)$user_id, ['is_like']);
        return returnSuccess($list);
    }

    public function getAnswerDetail(PostsService $postsService): array
    {
        $user_id = $this->request->getAttribute('user_id', 0);
        $comment_id = $this->request->input('answer_id', 0);
        $detail = $this->service->getCommentDetail((int)$comment_id, (int)$user_id);
        $detail->post_info = $postsService->getInfo($detail->post_id, ['is_like'], (int)$user_id);
        return returnSuccess($detail);
    }

    #[Middleware(ApiMiddleware::class)]
    public function answerShare()
    {
        $user_id = $this->request->getAttribute('user_id');
        $answer_id = $this->request->input('answer_id', 0);
        if (empty($answer_id)) {
            return returnError('参数错误');
        }
        $this->service->answerShare($user_id, $answer_id);
        return returnSuccess();
    }

    public function getCommentList(): array
    {
        $user_id = $this->request->getAttribute('user_id', 0);
        $params = $this->request->all();
        if (empty($params['answer_id']) && empty($params['post_id'])) {
            return returnError('参数错误');
        }
        $list = $this->service->getCommentList($params, (int)$user_id, ['reply', 'is_like']);
        return returnSuccess($list);
    }

    public function getReplyList(): array
    {
        $user_id = $this->request->getAttribute('user_id', 0);
        $params = $this->request->all();
        if (empty($params['comment_id'])) {
            return returnError('参数错误');
        }
        $list = $this->service->getReplyList($params, (int)$user_id);
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
        logGet('commentlike')->error(json_encode($params));
        $user_id = $this->request->getAttribute('user_id');
        $this->service->like($params['comment_id'], $user_id, $params['status'] ?? 1);
        return returnSuccess([], $params['status'] ? '点赞成功' : '已取消');
    }


    #[Middleware(ApiMiddleware::class)]
    #[Scene('comment_report')]
    public function report(ReportRequest $request, ReportService $reportService)
    {
        $data = $request->validated();
        $user_id = $this->request->getAttribute('user_id');
        $reportService->report($user_id, ReportType::COMMENT, $data);
        return returnSuccess();
    }
}