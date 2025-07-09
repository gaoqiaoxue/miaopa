<?php

namespace App\Service;

use Hyperf\DbConnection\Db;
use Hyperf\Di\Annotation\Inject;

class UserFollowService
{
    #[Inject]
    protected FileService $fileService;

    public function follow(int $user_id, int $follow_id, int $status): bool
    {
        $has = Db::table('user_follow')
            ->where(['follow_id' => $follow_id, 'user_id' => $user_id])
            ->count();
        if ((empty($has) && $status == 0) || (!empty($has) && $status == 1)) {
            return true;
        }
        if ($status == 1) {
            Db::table('user_follow')->insert([
                'follow_id' => $follow_id,
                'user_id' => $user_id,
                'create_time' => date('Y-m-d H:i:s'),
            ]);
        } else {
            Db::table('user_follow')
                ->where(['follow_id' => $follow_id, 'user_id' => $user_id])
                ->delete();
        }
        return true;
    }

    public function checkIsFollow(int $user_id, int $follow_id): int
    {
        $has = Db::table('user_follow')
            ->where(['follow_id' => $follow_id, 'user_id' => $user_id])
            ->count();
        return $has > 0 ? 1 : 0;
    }

    public function getFollowList(array $params): array
    {
        $query = Db::table('user_follow')
            ->leftJoin('user', 'user_follow.follow_id', '=', 'user.id')
            ->where('user_follow.user_id', $params['user_id']);
        if (!empty($params['keyword'])) {
            $query->where('user.nickname', 'like', "%{$params['keyword']}%");
        }
        $page = !empty($params['page']) ? $params['page'] : 1;
        $page_size = !empty($params['page_size']) ? $params['page_size'] : 10;
        $data = $query->select(['user.id', 'user.nickname', 'user.avatar', 'user_follow.create_time'])
            ->orderBy('user_follow.create_time', 'desc')
            ->paginate((int)$page_size, page: (int)$page);
        $list = paginateTransformer($data);
        $follow_ids = [];
        if (!empty($page['current_user_id']) && $params['current_user_id'] != $params['user_id']) {
            $follow_ids = Db::table('user_follow')
                ->where('user_id', $page['current_user_id'])
                ->pluck('follow_id')
                ->toArray();
        }
        foreach ($list['items'] as $item) {
            $item->avatar_url = $this->fileService->getAvatar($item->avatar);
            $item->is_self = $params['current_user_id'] == $item->id ? 1 : 0;
            if ($params['current_user_id'] == $params['user_id']) {
                $item->is_follow = 1;
            }else{
                $item->is_follow = in_array($item->id, $follow_ids) ? 1 : 0;
            }
        }
        return $list;
    }

    public function getFansList(array $params): array
    {
        $query = Db::table('user_follow')
            ->leftJoin('user', 'user_follow.user_id', '=', 'user.id')
            ->where('user_follow.follow_id', $params['user_id']);
        if (!empty($params['keyword'])) {
            $query->where('user.nickname', 'like', "%{$params['keyword']}%");
        }
        $page = !empty($params['page']) ? $params['page'] : 1;
        $page_size = !empty($params['page_size']) ? $params['page_size'] : 10;
        $data = $query->select(['user.id', 'user.nickname', 'user.avatar', 'user_follow.create_time'])
            ->orderBy('id', 'desc')
            ->paginate((int)$page_size, page: (int)$page);
        $list = paginateTransformer($data);
        $follow_ids = [];
        if (!empty($page['current_user_id'])) {
            $follow_ids = Db::table('user_follow')
                ->where('user_id', $page['current_user_id'])
                ->pluck('follow_id')
                ->toArray();
        }
        foreach ($list['items'] as $item) {
            $item->avatar_url = $this->fileService->getAvatar($item->avatar);
            $item->is_self = $params['current_user_id'] == $item->id ? 1 : 0;
            $item->is_follow = in_array($item->id, $follow_ids) ? 1 : 0;
        }
        return $list;
    }
}