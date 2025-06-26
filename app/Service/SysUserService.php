<?php

namespace App\Service;

use App\Exception\LogicException;
use App\Library\Contract\AuthTokenInterface;
use Hyperf\DbConnection\Db;

class SysUserService
{
    protected AuthTokenInterface $authToken;

    public function login(array $data): array
    {
        $user = Db::table('sys_user')->where(['user_name' => $data['user_name']])->first();
        if (!$user)
            throw new LogicException('账号不存在');
        if (!checkPassword($data['password'], $user->password))
            throw new LogicException('账号或密码错误');
        $user_data = [
            'user_id' => $user->user_id,
            'user_name' => $user->user_name,
        ];
        $token = $this->authToken->createToken($user_data, 'admin');
        return [
            'user' => $user,
            'token' => $token,
        ];
    }

    public function refreshToken(): array
    {
        return $this->authToken->refreshToken();
    }

    public function logout()
    {
        if (!$this->authToken->logout()) {
            throw new LogicException('退出失败');
        }
        return true;
    }

    public function getSysUserList(array $params = [])
    {
        $query = Db::table('sys_user')
            ->leftJoin('sys_user_role', 'sys_user.user_id', '=', 'sys_user_role.user_id')
            ->leftJoin('sys_role', 'sys_user_role.role_id', '=', 'sys_role.role_id');
        if (!empty($params['user_name'])) {
            $query->where('sys_user.user_name', 'like', '%' . $params['user_name'] . '%');
        }
        if (!empty($params['nick_name'])) {
            $query->where('sys_user.nick_name', 'like', '%' . $params['nick_name'] . '%');
        }
        if (!empty($params['phonenumber'])) {
            $query->where('sys_user.phonenumber', 'like', '%' . $params['phonenumber'] . '%');
        }
        if (isset($params['status']) && in_array($params['status'], [0, 1])) {
            $query->where('sys_user.status', '=', $params['status']);
        }
        if (!empty($params['create_time_start']) && !empty($params['create_time_end'])) {
            $query->whereBetween('sys_user.create_time', [$params['create_time_start'], $params['create_time_end']]);
        }
        $page = !empty($params['page']) ? $params['page'] : 1;
        $page_size = !empty($params['page_size']) ? $params['page_size'] : 15;
        $data = $query->select(['sys_user.user_id', 'sys_user.user_name', 'sys_user.nick_name', 'sys_user.phonenumber', 'sys_user.create_time', 'sys_user.status', 'sys_role.role_name'])
            ->orderBy('user_id', 'desc')
            ->paginate((int)$page_size, page: (int)$page);
        return paginateTransformer($data);
    }

    public function getInfo(int $user_id): object
    {
        $user = Db::table('sys_user')
            ->leftJoin('sys_user_role', 'sys_user.user_id', '=', 'sys_user_role.user_id')
            ->leftJoin('sys_role', 'sys_user_role.role_id', '=', 'sys_role.role_id')
            ->where(['sys_user.user_id' => $user_id])
            ->select(['sys_user.user_id', 'sys_user.user_name', 'sys_user.nick_name', 'sys_user.phonenumber', 'sys_user.create_time', 'sys_user.status', 'sys_role.role_name'])
            ->first();
        if (!$user) {
            throw new LogicException('用户不存在');
        }
        return $user;
    }

    public function add(array $data): int
    {
        $has = Db::table('sys_user')->where(['user_name' => $data['user_name']])->first();
        if ($has) {
            throw new LogicException('账号不可重复');
        }
        Db::beginTransaction();
        try {
            $user_id = Db::table('sys_user')->insertGetId([
                'user_name' => $data['user_name'],
                'nick_name' => $data['nick_name'],
                'phonenumber' => $data['phonenumber'],
                'password' => setPassword($data['password']),
                'create_time' => date('Y-m-d H:i:s'),
                'create_by' => $data['create_by'],
                'status' => 1,
                'avatar' => $data['avatar'] ?? ''
            ]);
            Db::table('sys_user_role')->insert(['user_id' => $user_id, 'role_id' => $data['role_id']]);
            Db::commit();
        } catch (\Throwable $ex) {
            Db::rollBack();
            throw new LogicException($ex->getMessage());
        }
        return $user_id;
    }

    public function edit(array $data): bool
    {
        $update = [
            'user_name' => $data['user_name'],
            'nick_name' => $data['nick_name'],
            'phonenumber' => $data['phonenumber'],
            'update_time' => date('Y-m-d H:i:s'),
            'update_by' => $data['update_by'],
        ];
        if (!empty($data['password']))
            $update['password'] = setPassword($data['password']);
        if (!empty($data['avatar']))
            $update['avatar'] = $data['avatar'];
        $user_id = $data['user_id'];
        $has = Db::table('sys_user')
            ->where(['user_name' => $data['user_name']])
            ->where('user_id', '<>', $user_id)
            ->first();
        if ($has) {
            throw new LogicException('账号不可重复');
        }
        if ($user_id == 1 && $data['role_id'] != 1) {
            throw new LogicException('超级管理员不可修改角色');
        }
        Db::beginTransaction();
        try {
            Db::table('sys_user')->where(['user_id' => $user_id])->update($update);
            Db::table('sys_user_role')->where(['user_id' => $user_id])->update(['role_id' => $data['role_id']]);
            Db::commit();
        } catch (\Throwable $ex) {
            Db::rollBack();
            throw new LogicException($ex->getMessage());
        }
        return true;
    }

    public function changeStatus(int $user_id, int $status, int $update_by): bool
    {
        if ($user_id == 1 && $status == 0) {
            throw new LogicException('超级管理员不可禁用');
        }
        Db::table('sys_user')->where(['user_id' => $user_id])->update([
            'status' => $status,
            'update_by' => $update_by,
            'update_time' => date('Y-m-d H:i:s')
        ]);
        return true;
    }

    public function changePassword(int $user_id, string $password, int $update_by): bool
    {
        Db::table('sys_user')->where(['user_id' => $user_id])->update([
            'password' => setPassword($password),
            'update_by' => $update_by,
            'update_time' => date('Y-m-d H:i:s')
        ]);
        return true;
    }
}
