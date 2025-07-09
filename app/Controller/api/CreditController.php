<?php

namespace App\Controller\api;

use App\Controller\AbstractController;
use App\Middleware\ApiMiddleware;
use App\Service\CreditService;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\AutoController;
use Hyperf\HttpServer\Annotation\Middleware;

#[AutoController]
#[Middleware(ApiMiddleware::class)]
class CreditController extends AbstractController
{
    #[Inject]
    protected CreditService $service;

    /**
     * 金币流水
     * @return array
     */
    public function getCoinLog():array
    {
        $user_id = $this->request->getAttribute('user_id');
        $params = $this->request->all();
        $params['user_id'] = $user_id;
        $list = $this->service->getCoinLogs($params);
        return returnSuccess($list);
    }

}