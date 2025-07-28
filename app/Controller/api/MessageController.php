<?php

namespace App\Controller\api;

use App\Controller\AbstractController;
use App\Middleware\ApiBaseMiddleware;
use App\Middleware\ApiMiddleware;
use App\Service\MessageService;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\AutoController;
use Hyperf\HttpServer\Annotation\Middleware;

#[AutoController]
#[Middleware(ApiBaseMiddleware::class)]
class MessageController extends AbstractController
{
    #[Inject]
    protected MessageService $service;

    public function index()
    {
        $user_id = $this->request->getAttribute('user_id', 0);
        if (empty($user_id)) {
            return returnSuccess([], '用户未登录');
        }
        $result = $this->service->getMessageList($user_id);
        return returnSuccess($result);
    }

    #[Middleware(ApiMiddleware::class)]
    public function getLikeList()
    {
        $user_id = $this->request->getAttribute('user_id');
        $params = $this->request->all();
        $result = $this->service->getLikeMessage($user_id, $params);
        return returnSuccess($result);
    }

    #[Middleware(ApiMiddleware::class)]
    public function getFansList()
    {
        $user_id = $this->request->getAttribute('user_id');
        $params = $this->request->all();
        $result = $this->service->getFansMessage($user_id, $params);
        return returnSuccess($result);
    }

    #[Middleware(ApiMiddleware::class)]
    public function getCommentList()
    {
        $user_id = $this->request->getAttribute('user_id');
        $params = $this->request->all();
        $result = $this->service->getCommentMessage($user_id, $params);
        return returnSuccess($result);
    }

    #[Middleware(ApiMiddleware::class)]
    public function getSystemList()
    {
        $user_id = $this->request->getAttribute('user_id');
        $params = $this->request->all();
        $result = $this->service->getSystemMessage($user_id, $params);
        return returnSuccess($result);
    }

    #[Middleware(ApiMiddleware::class)]
    public function setRead()
    {
        $user_id = $this->request->getAttribute('user_id');
        $params = $this->request->all();
        $this->service->setRead($user_id, $params);
        return returnSuccess();
    }

}