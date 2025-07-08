<?php

namespace App\Controller\api;

use App\Controller\AbstractController;
use App\Middleware\ApiMiddleware;
use App\Service\UserSignService;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\AutoController;
use Hyperf\HttpServer\Annotation\Middleware;

#[AutoController]
#[Middleware(ApiMiddleware::class)]
class SignController extends AbstractController
{
    #[Inject]
    protected UserSignService $service;

    public function getInfo(): array
    {
        $user_id = $this->request->getAttribute('user_id');
        $info = $this->service->getInfo($user_id);
        return returnSuccess($info);
    }

    public function sign(): array
    {
        $user_id = $this->request->getAttribute('user_id');
        $this->service->sign($user_id);
        return returnSuccess([],'签到成功');
    }

}