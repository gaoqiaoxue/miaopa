<?php

namespace App\Service;

use App\Constants\Sex;
use App\Exception\LogicException;
use App\Library\Contract\AuthTokenInterface;
use Hyperf\DbConnection\Db;
use Hyperf\Di\Annotation\Inject;

class UserService
{

    public function getList(array $params): array
    {
        $query = Db::table('user');
        if (!empty($params['username'])) {
            $query->where('username', 'like', '%' . $params['username'] . '%');
        }
        if (!empty($params['nickname'])) {
            $query->where('nickname', 'like', '%' . $params['nickname'] . '%');
        }
        if (!empty($params['mobile'])) {
            $query->where('mobile', 'like', '%' . $params['mobile'] . '%');
        }
        if (isset($params['sex']) && in_array($params['sex'], Sex::getKeys())) {
            $query->where('sex', '=', $params['sex']);
        }
        if (!empty($params['id'])) {
            $query->where('id', '=', $params['id']);
        }
        $page = !empty($params['page']) ? $params['page'] : 1;
        $page_size = !empty($params['page_size']) ? $params['page_size'] : 15;
        $columns = ['id', 'name', 'username', 'nickname', 'sex', 'avatar', 'signature', 'region', 'school', 'mobile', 'create_time'];
        $data = $query->select($columns)
            ->orderBy('create_time', 'desc')
            ->paginate((int)$page_size, page: (int)$page);
        return paginateTransformer($data);
    }

    public function getInfo(int $id): object
    {
        $columns = ['id', 'name', 'username', 'nickname', 'sex', 'avatar', 'signature', 'region', 'school', 'mobile', 'create_time'];
        $user = Db::table('user')->where(['id' => $id])->select($columns)->first();
        if (!$user) {
            throw new LogicException('用户不存在');
        }
        $user->avatar_url = getAvatar($user->avatar);
        $user->created_days = $this->getCreatedDays($user->create_time);
        // TODO 获取声望值
        $user->prestige = 0;
        return $user;
    }

    protected function getCreatedDays($create_time)
    {
        $start = strtotime($create_time);
        $end = time();
        return ceil(abs($end - $start) / 86400);
    }
}