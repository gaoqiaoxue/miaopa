<?php

namespace App\Controller\admin;

use App\Controller\AbstractController;
use App\Middleware\AdminMiddleware;
use App\service\SysRoleService;
use Hyperf\HttpServer\Annotation\AutoController;
use Hyperf\HttpServer\Annotation\Middleware;

#[AutoController]
#[Middleware(AdminMiddleware::class)]
class SysRoleController extends AbstractController
{
    protected SysRoleService $service;

    public function getSysRoleList(): array
    {
        $params = $this->request->all();
        $list = $this->service->getSysRoleList($params);
        return returnSuccess($list);
    }

}