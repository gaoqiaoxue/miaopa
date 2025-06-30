<?php

namespace App\Controller\admin;

use App\Controller\AbstractController;
use App\Middleware\AdminMiddleware;
use App\Request\ConfigRequest;
use App\Service\ConfigService;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\AutoController;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\Validation\Annotation\Scene;

/**
 * 配置管理
 */
#[AutoController]
#[Middleware(AdminMiddleware::class)]
class ConfigController extends AbstractController
{
    #[Inject]
    protected ConfigService $service;

    public function getConfig()
    {
        $configs = $this->service->getConfig();
        return returnSuccess($configs);
    }

    #[Scene('publish')]
    public function setPublish(ConfigRequest $request)
    {
        $params = $request->validated();
        $this->service->update($params);
        return returnSuccess();
    }

    #[Scene('coins')]
    public function setCoins(ConfigRequest $request)
    {
        $params = $request->validated();
        $this->service->update($params);
        return returnSuccess();
    }

}