<?php

namespace App\Service;

use Hyperf\DbConnection\Db;

/**
 * 后台账号权限相关
 */
class SysRoleService
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

    public function getSysRoleList(array $params)
    {
        $query = Db::table('sys_role');
        if(!empty($params['role_name'])){
            $query->where('role_name','like','%'.$params['role_name'].'%');
        }
        $page = !empty($params['page']) ? $params['page'] : 1;
        $page_size = !empty($params['page_size']) ? $params['page_size'] : 15;
        $data = $query->select(['role_id', 'role_name', 'remark', 'status', 'create_time'])
            ->orderBy('create_time', 'desc')
            ->paginate((int)$page_size, page: (int)$page);
        $data = paginateTransformer($data);
        $items = $data['items'];
        $role_ids = array_column($items, 'role_id');
        // 查找角色对应用户数
        $user_count = Db::table('sys_user_role')
            ->whereIn('role_id', $role_ids)
            ->groupBy('role_id')
            ->select('role_id',Db::raw('count(user_id) as count'))
            ->pluck('count','role_id')
            ->toArray();
        foreach ($items as &$item) {
            $item->user_count = $user_count[$item->role_id] ?? 0;
        }
        $data['items'] = $items;
        return $data;
    }
}