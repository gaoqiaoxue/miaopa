<?php

namespace App\Controller\api;

use App\Controller\AbstractController;
use App\Middleware\ApiBaseMiddleware;
use App\Middleware\ApiMiddleware;
use App\Request\RoleRequest;
use App\Service\RoleService;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\AutoController;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\Validation\Annotation\Scene;

#[AutoController]
#[Middleware(ApiBaseMiddleware::class)]
class RoleController extends AbstractController
{
    #[Inject]
    protected RoleService $roleService;

    #[Scene('user_add')]
    #[Middleware(ApiMiddleware::class)]
    public function add(RoleRequest $request): array
    {
        $params = $request->validated();
        $user_id = $this->request->getAttribute('user_id');
        $role_id = $this->roleService->add($params, $user_id, 'user');
        return returnSuccess(['id' => $role_id]);
    }

    #[Scene('id')]
    public function detail(RoleRequest $request):array
    {
        $params = $request->validated();
        $detail = $this->roleService->getInfo($params['role_id'],['circle']);
        return returnSuccess($detail);
    }
}