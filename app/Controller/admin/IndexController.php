<?php

namespace App\Controller\admin;

use App\Controller\AbstractController;
use App\Middleware\AdminMiddleware;
use App\Request\SysUserRequest;
use App\Service\SysRoleService;
use App\Service\SysUserService;
use Hyperf\DbConnection\Db;
use Hyperf\HttpServer\Annotation\AutoController;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\Validation\Annotation\Scene;

#[AutoController]
#[Middleware(AdminMiddleware::class)]
class IndexController extends AbstractController
{
    public function index()
    {
//        $user_data = $this->request->getAttribute("user_data");
        return [
//            'data' => $user_data,
            'avatar' => getAvatar(5)
        ];
    }


}