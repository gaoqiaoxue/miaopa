<?php

namespace App\service;

use Hyperf\DbConnection\Db;

/**
 * 后台账号权限相关
 */
class SysPermissionService
{
    // 获取所有菜单列表
    public function getMenus(int $role_id = 0): array
    {
        $menus = $this->getAllMenus();
    }

    protected function getAllMenus(): array
    {
        $columns = ['menu_id', 'menu_name', 'parent_id', 'path', 'component', 'query', 'menu_type', 'icon', 'is_frame', 'is_cache', 'visible', 'status', 'perms',];
        return Db::table('sys_menus')
            ->where(['del_flag' => 0])
            ->orderBy('order_num asc')
            ->select($columns)
            ->get()
            ->toArray();
    }
}