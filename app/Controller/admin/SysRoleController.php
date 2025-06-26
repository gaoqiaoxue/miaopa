<?php

namespace App\Controller\admin;

use App\Controller\AbstractController;
use App\Middleware\AdminMiddleware;
use App\Service\SysRoleService;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\AutoController;
use Hyperf\HttpServer\Annotation\Middleware;

#[AutoController]
#[Middleware(AdminMiddleware::class)]
class SysRoleController extends AbstractController
{
    #[Inject]
    protected SysRoleService $service;

    public function getSysRoleList(): array
    {
        $params = $this->request->all();
        $list = $this->service->getSysRoleList($params);
        return returnSuccess($list);
    }

}