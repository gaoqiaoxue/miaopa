<?php

namespace App\Service;

use App\Constants\SysStatus;
use App\Exception\LogicException;
use Hyperf\DbConnection\Db;

/**
 * 后台账号权限相关
 */
class SysRoleService
{
    public function getSysRoleSelect()
    {
        return Db::table('sys_role')->where('status', SysStatus::ENABLE)->select(['role_id', 'role_name'])->get();
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

    // 获取所有菜单列表
    public function getMenus(int $role_id = 0): array
    {
        $menus = $this->getAllMenus();
        if($role_id == 0){
            foreach ($menus as $menu) {
                $menu->checked = false;
            }
            return arrayToTree($menus,0,'menu_id', 'parent_id');
        }
        $role_menus = Db::table('sys_role_menu')
            ->where(['role_id' => $role_id])
            ->select(['menu_id'])
            ->pluck('menu_id')
            ->toArray();
        foreach ($menus as $menu) {
            $menu->checked = in_array($menu->menu_id, $role_menus);
        }
        return arrayToTree($menus,0,'menu_id', 'parent_id');
    }

    protected function getAllMenus(array $params = []): array
    {
        $columns = ['menu_id', 'menu_name', 'parent_id', 'path', 'component', 'query', 'menu_type', 'icon', 'is_frame', 'is_cache', 'visible', 'status', 'perms',];
        $query = Db::table('sys_menu')
            ->where(['del_flag' => 0]);
        if(!empty($params['menu_ids'])){
            $query->whereIn('menu_id', $params['menu_ids']);
        }
        return $query->orderBy('order_num','asc')
            ->select($columns)
            ->get()
            ->toArray();
    }

    public function getInfo(int $role_id):object
    {
        $role = Db::table('sys_role')->where(['role_id' => $role_id])
            ->select(['role_id', 'role_name', 'remark', 'status', 'create_time'])
            ->first();
        if (!$role) {
            throw new LogicException('角色不存在');
        }
        return $role;
    }

    public function add(array $data):int
    {
        Db::beginTransaction();
        try {
            $role_id = Db::table('sys_role')->insertGetId([
                'role_name' => $data['role_name'],
                'remark' => $data['remark'],
                'status' => SysStatus::ENABLE,
                'create_by' => $data['create_by'] ?? 0,
                'create_time' => date('Y-m-d H:i:s'),
            ]);
            $menu_ids = $data['menu_ids'];
            $role_menu = [];
            foreach ($menu_ids as $menu_id) {
                $role_menu[] = [
                    'role_id' => $role_id,
                    'menu_id' => $menu_id,
                ];
            }
            Db::table('sys_role_menu')->insert($role_menu);
            Db::commit();
        }catch (\Throwable $ex) {
            Db::rollBack();
            throw new LogicException($ex->getMessage());
        }
        return $role_id;
    }

    public function edit(array $data):bool
    {
        $role = Db::table('sys_role')->where(['role_id' => $data['role_id']])->first();
        if (!$role) {
            throw new LogicException('角色不存在');
        }
        Db::beginTransaction();
        try {
            Db::table('sys_role')
                ->where(['role_id' => $data['role_id']])
                ->update([
                    'role_name' => $data['role_name'],
                    'remark' => $data['remark'],
                    'update_by' => $data['update_by'] ?? 0,
                    'update_time' => date('Y-m-d H:i:s'),
                ]);
            Db::commit();
        }catch (\Throwable $ex) {
            Db::rollBack();
            throw new LogicException($ex->getMessage());
        }
        return true;
    }

    public function changeStatus(int $role_id, int $status, int $update_by):bool
    {
        if ($role_id == 1 && $status == SysStatus::DISABLE->value) {
            throw new LogicException('超级管理员角色不可禁用');
        }
        $role = Db::table('sys_role')->where(['role_id' => $role_id])->first();
        if (!$role) {
            throw new LogicException('角色不存在');
        }
        Db::table('sys_role')->where(['role_id' => $role_id])->update([
            'status' => $status,
            'update_by' => $update_by,
            'update_time' => date('Y-m-d H:i:s'),
        ]);
        return true;
    }

    public function changePerms(int $role_id, array $menu_ids, int $update_by):bool
    {
        if($role_id == 1){
            throw new LogicException('超级管理员角色不可修改权限');
        }
        $role = Db::table('sys_role')->where(['role_id' => $role_id])->first();
        if (!$role) {
            throw new LogicException('角色不存在');
        }
        Db::beginTransaction();
        try {
            Db::table('sys_role_menu')->where(['role_id' => $role_id])->delete();
            $role_menu = [];
            foreach ($menu_ids as $menu_id) {
                $role_menu[] = [
                    'role_id' => $role_id,
                    'menu_id' => $menu_id,
                ];
            }
            Db::table('sys_role_menu')->insert($role_menu);
            Db::table('sys_role')->where(['role_id' => $role_id])->update([
                'update_by' => $update_by,
                'update_time' => date('Y-m-d H:i:s'),
            ]);
            Db::commit();
        }catch (\Throwable $ex) {
            Db::rollBack();
            throw new LogicException($ex->getMessage());
        }
        return true;
    }


    public function getRolePerms(int $role_id):array
    {
        $params = [];
        if($role_id != 1){
            $menu_ids = Db::table('sys_role_menu')
                ->where(['role_id' => $role_id])
                ->pluck('menu_id')
                ->toArray();
            $params['menu_ids'] = $menu_ids;
        }
        $menus = $this->getAllMenus($params);
        return arrayToTree($menus,0,'menu_id', 'parent_id');
    }
}