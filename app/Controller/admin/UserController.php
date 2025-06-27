<?php

namespace App\Controller\admin;

use App\Controller\AbstractController;
use App\Middleware\AdminMiddleware;
use App\Service\UserService;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\AutoController;
use Hyperf\HttpServer\Annotation\Middleware;

/**
 * 用户管理
 */
#[AutoController]
#[Middleware(AdminMiddleware::class)]
class UserController extends AbstractController
{
    #[Inject]
    protected UserService $service;

    /**
     * 获取用户列表
     * @return array
     */
    public function getList():array
    {
        $params = $this->request->all();
        $list = $this->service->getList($params);
        return returnSuccess($list);

    }

    /**
     * 获取用户详情
     * @return array
     */
    public function getInfo():array
    {
        $id = $this->request->input('id');
        $info = $this->service->getInfo($id);
        return returnSuccess($info);
    }

}