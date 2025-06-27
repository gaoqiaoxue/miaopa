<?php

namespace App\Controller\admin;

use App\Controller\AbstractController;
use App\Middleware\AdminMiddleware;
use App\Request\SysRoleRequest;
use App\Service\SysRoleService;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\AutoController;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\Validation\Annotation\Scene;

#[AutoController]
#[Middleware(AdminMiddleware::class)]
class SysRoleController extends AbstractController
{
    #[Inject]
    protected SysRoleService $service;

    /**
     * 获取所有角色列表，用户select选项
     * @return void
     */
    public function getSysRoleSelect():array
    {
        $list = $this->service->getSysRoleSelect();
        return returnSuccess($list);
    }

    /**
     * 获取角色列表
     * @return array
     */
    public function getSysRoleList(): array
    {
        $params = $this->request->all();
        $list = $this->service->getSysRoleList($params);
        return returnSuccess($list);
    }

    /**
     * 获取菜单列表
     * @return array
     */
    public function getMenus():array
    {
        $role_id = $this->request->input('role_id', 0);
        $list = $this->service->getMenus($role_id);
        return returnSuccess($list);
    }

    /**
     * 新增角色
     * @param SysRoleRequest $request
     * @return array
     */
    #[Scene('add')]
    public function add(SysRoleRequest $request)
    {
        $current_user_id = $this->request->getAttribute('user_id');
        $data = $request->validated();
        $data['create_by'] = $current_user_id;
        $this->service->add($data);
        return returnSuccess();
    }

    /**
     * 获取角色信息
     * @return array
     */
    public function getInfo():array
    {
        $role_id = $this->request->input('role_id', 0);
        $role = $this->service->getInfo($role_id);
        return returnSuccess($role);
    }

    /**
     * 编辑角色
     * @return array
     */
    #[Scene('edit')]
    public function edit(SysRoleRequest $request):array
    {
        $current_user_id = $this->request->getAttribute('user_id');
        $data = $request->validated();
        $data['update_by'] = $current_user_id;
        $this->service->edit($data);
        return returnSuccess();
    }

    /**
     * 启用禁用角色
     * @return array
     */
    #[Scene('changeStatus')]
    public function changeStatus(SysRoleRequest $request)
    {
        $current_user_id = $this->request->getAttribute('user_id');
        $data = $request->validated();
        $this->service->changeStatus($data['role_id'], $data['status'],$current_user_id);
        return returnSuccess();
    }

    /**
     * 编辑角色权限
     * @return array
     */
    #[Scene('changePerms')]
    public function changePerms(SysRoleRequest $request):array
    {
        $current_user_id = $this->request->getAttribute('user_id');
        $data = $request->validated();
        $this->service->changePerms($data['role_id'], $data['menu_ids'], $current_user_id);
        return returnSuccess();
    }
}