<?php

namespace App\Controller\api;


use App\Controller\AbstractController;
use App\Library\Contract\MapWebInterface;
use App\Library\WechatOfficialLib;
use App\Middleware\ApiMiddleware;
use App\Service\ConfigService;
use Hyperf\DbConnection\Db;
use Hyperf\HttpServer\Annotation\AutoController;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\Logger\LoggerFactory;

#[AutoController]
#[Middleware(ApiMiddleware::class)]
class IndexController extends AbstractController
{
    public function index(MapWebInterface $service){
        logGet('info','wxmini')->debug('serre');
        return [
            'data' => $this->request->all()
//            'value' => $service->getLatLonByAddress('郑州市高新区正弘汇')
        ];
    }

}