<?php

namespace App\Controller\admin;

use App\Controller\AbstractController;
use App\Middleware\AdminMiddleware;
use App\Request\RoleRequest;
use App\Service\RoleService;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\AutoController;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\Validation\Annotation\Scene;

#[AutoController]
#[Middleware(AdminMiddleware::class)]
class RoleController extends AbstractController
{
    #[Inject]
    protected RoleService $roleService;

    public function getList(): array
    {
        $params = $this->request->all();
        $list = $this->roleService->getList($params);
        return returnSuccess($list);
    }

    #[Scene('id')]
    public function getInfo(RoleRequest $request): array
    {
        $id = $request->input('role_id');
        $info = $this->roleService->getInfo($id);
        return returnSuccess($info);
    }

    #[Scene('add')]
    public function add(RoleRequest $request): array
    {
        $params = $request->validated();
        $cur_user_id = $this->request->getAttribute('user_id');
        $role_id = $this->roleService->add($params, $cur_user_id);
        return returnSuccess(['id' => $role_id]);
    }

    #[Scene('edit')]
    public function edit(RoleRequest $request): array
    {
        $params = $request->validated();
        $this->roleService->edit($params);
        return returnSuccess();
    }

    #[Scene('change_status')]
    public function changeStatus(RoleRequest $request): array
    {
        $params = $request->validated();
        $this->roleService->changeStatus($params['role_id'], $params['status']);
        return returnSuccess();
    }
}