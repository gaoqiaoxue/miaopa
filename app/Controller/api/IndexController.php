<?php

namespace App\Controller\api;


use App\Controller\AbstractController;
use App\Library\WechatOfficialLib;
use App\Middleware\ApiMiddleware;
use App\Service\ConfigService;
use Hyperf\DbConnection\Db;
use Hyperf\HttpServer\Annotation\AutoController;
use Hyperf\HttpServer\Annotation\Middleware;

#[AutoController]
#[Middleware(ApiMiddleware::class)]
class IndexController extends AbstractController
{
    public function index(ConfigService $service){

//        $user_data = $this->request->getAttribute("user_data");
        return [
//            'data' => $user_data,
            'value' => $service->getValue('123')
        ];
    }

}