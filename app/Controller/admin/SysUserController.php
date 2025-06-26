<?php

namespace App\Controller\admin;

use App\Controller\AbstractController;
use App\Middleware\AdminMiddleware;
use App\Request\SysUserRequest;
use App\Service\SysRoleService;
use App\Service\SysUserService;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\AutoController;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\Validation\Annotation\Scene;

/**
 * 后台账号管理
 */
#[AutoController]
#[Middleware(AdminMiddleware::class)]
class SysUserController extends AbstractController
{
    #[Inject]
    protected SysUserService $userService;

    #[Inject]
    protected SysRoleService $permissionService;

    public function getSysUserList(): array
    {
        $params = $this->request->all();
        $list = $this->userService->getSysUserList($params);
        return returnSuccess($list);
    }

    #[Scene('add')]
    public function add(SysUserRequest $request):array
    {
        $cur_user_id = $this->request->getAttribute("user_id");
        $data = $request->validated();
        $data['create_by'] = $cur_user_id;
        $user_id = $this->userService->add($data);
        return returnSuccess(['user_id' => $user_id]);
    }

    public function getInfo():array
    {
        $user_id = $this->request->input('user_id');
        $info = $this->userService->getInfo($user_id);
        return returnSuccess($info);
    }

    #[Scene('edit')]
    public function edit(sysUserRequest $request):array
    {
        $cur_user_id = $this->request->getAttribute("user_id");
        $data = $request->validated();
        $data['update_by'] = $cur_user_id;
        $this->userService->edit($data);
        return returnSuccess();
    }

    #[Scene('change_status')]
    public function changeStatus(sysUserRequest $request):array
    {
        $cur_user_id = $this->request->getAttribute("user_id");
        $data = $request->validated();
        $this->userService->changeStatus($data['user_id'],$data['status'],$cur_user_id);
        return returnSuccess();
    }

    #[Scene('change_psw')]
    public function changePassword(sysUserRequest $request):array
    {
        $cur_user_id = $this->request->getAttribute("user_id");
        $data = $request->validated();
        $this->userService->changePassword($data['user_id'],$data['password'], $cur_user_id);
        return returnSuccess();
    }
}