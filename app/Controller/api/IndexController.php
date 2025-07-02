<?php

namespace App\Controller\api;


use App\Controller\AbstractController;
use App\Library\WechatOfficialLib;
use App\Middleware\ApiMiddleware;
use Hyperf\HttpServer\Annotation\AutoController;
use Hyperf\HttpServer\Annotation\Middleware;

#[AutoController]
#[Middleware(ApiMiddleware::class)]
class IndexController extends AbstractController
{
    public function index(WechatOfficialLib $wechatOfficialLib){
//        $user_data = $this->request->getAttribute("user_data");
        return [
//            'data' => $user_data,
            'path' =>$wechatOfficialLib->getRedirectUrl()
//            'data' => Db::table('sys_user')->where(['user_id' => 1])->get()
        ];
    }

}