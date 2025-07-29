<?php

namespace App\Service;

use App\Constants\PrestigeCate;
use App\Exception\LogicException;
use Hyperf\DbConnection\Db;
use Hyperf\Di\Annotation\Inject;

class UserFollowService
{
    #[Inject]
    protected CreditService $creditService;

    #[Inject]
    protected MessageService $messageService;

    public function follow(int $user_id, int $follow_id, int $status): bool
    {
        if($user_id == $follow_id){
            throw new LogicException('不能关注自己');
        }
        $has = Db::table('user_follow')
            ->where(['follow_id' => $follow_id, 'user_id' => $user_id])
            ->count();
        if ((empty($has) && $status == 0) || (!empty($has) && $status == 1)) {
            return true;
        }
        Db::beginTransaction();
        if ($status == 1) {
            Db::table('user_follow')->insert([
                'follow_id' => $follow_id,
                'user_id' => $user_id,
                'create_time' => date('Y-m-d H:i:s'),
            ]);
            Db::table('user')->where('id', '=', $follow_id)->increment('fans_num');
            Db::table('user')->where('id', '=', $user_id)->increment('follow_num');
            $this->followSuccess($user_id, $follow_id);
        } else {
            Db::table('user_follow')
                ->where(['follow_id' => $follow_id, 'user_id' => $user_id])
                ->delete();
            Db::table('user')->where('id', '=', $follow_id)->decrement('fans_num');
            Db::table('user')->where('id', '=', $user_id)->decrement('follow_num');
        }
        Db::commit();
        return true;
    }

    public function followSuccess(int $user_id, int $follow_id)
    {
        // 声望
        $this->creditService->finishPrestigeTask($follow_id, PrestigeCate::FANS, $user_id, '被关注');
        $this->creditService->finishPrestigeTask($user_id, PrestigeCate::FOLLOW, $follow_id, '关注');
        // 用户消息
        $this->messageService->addFansMessage($follow_id,  $user_id);
    }

    public function checkIsFollow(int $user_id, int $follow_id): int
    {
        $has = Db::table('user_follow')
            ->where(['follow_id' => $follow_id, 'user_id' => $user_id])
            ->count();
        return $has > 0 ? 1 : 0;
    }

    // 获取用户的关注列表
    public function getFollowList(array $params): array
    {
        $query = Db::table('user_follow')
            ->leftJoin('user', 'user_follow.follow_id', '=', 'user.id')
            ->where('user_follow.user_id', $params['user_id']);
        if (!empty($params['keyword'])) {
            $query->where('user.nickname', 'like', "%{$params['keyword']}%");
        }
        $page = !empty($params['page']) ? $params['page'] : 1;
        $page_size = !empty($params['page_size']) ? $params['page_size'] : 15;
        $data = $query->select(['user.id', 'user.nickname', 'user.avatar', 'user.show_icon', 'user.avatar_icon', 'user_follow.create_time'])
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
            $item->user_info = generalAPiUserInfo($item);
            $item->is_self = $params['current_user_id'] == $item->id ? 1 : 0;
            if ($params['current_user_id'] == $params['user_id']) {
                $item->is_follow = 1;
            }else{
                $item->is_follow = in_array($item->id, $follow_ids) ? 1 : 0;
            }
        }
        return $list;
    }

    // 获取用户的粉丝列表
    public function getFansList(array $params): array
    {
        $query = Db::table('user_follow')
            ->leftJoin('user', 'user_follow.user_id', '=', 'user.id')
            ->where('user_follow.follow_id', $params['user_id']);
        if (!empty($params['keyword'])) {
            $query->where('user.nickname', 'like', "%{$params['keyword']}%");
        }
        $page = !empty($params['page']) ? $params['page'] : 1;
        $page_size = !empty($params['page_size']) ? $params['page_size'] : 15;
        $data = $query->select(['user.id', 'user.nickname', 'user.avatar', 'user.show_icon', 'user.avatar_icon', 'user_follow.create_time'])
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
            $item->user_info = generalAPiUserInfo($item);
            $item->is_self = $params['current_user_id'] == $item->id ? 1 : 0;
            $item->is_follow = in_array($item->id, $follow_ids) ? 1 : 0;
        }
        return $list;
    }

    // 获取用户在限定用户用的关注列表
    public function getFollowIds(int $user_id, array $follow_ids):array
    {
        if(empty($user_id) || empty($follow_ids)){
            return [];
        }
        return Db::table('user_follow')
            ->where('user_id', $user_id)
            ->whereIn('follow_id', $follow_ids)
            ->pluck('follow_id')
            ->toArray();
    }
}