<?php

namespace App\Service;

use App\Constants\IsRisky;
use App\Constants\Sex;
use App\Exception\LogicException;
use App\Library\Contract\AuthTokenInterface;
use App\Library\WechatMiniAppLib;
use Hyperf\Cache\Annotation\Cacheable;
use Hyperf\DbConnection\Db;
use Hyperf\Di\Annotation\Inject;

class UserService
{
    #[Inject]
    protected CreditService $creditService;

    #[Inject]
    protected UserFollowService $followService;

    #[Inject]
    protected WechatMiniAppLib $mini_lib;

    #[Inject]
    protected MediaAuditService $mediaAuditService;

    public function getList(array $params): array
    {
        $query = $this->buildQuery($params);
        $page = !empty($params['page']) ? $params['page'] : 1;
        $page_size = !empty($params['page_size']) ? $params['page_size'] : 15;
        $columns = ['id', 'name', 'username', 'nickname', 'sex', 'avatar', 'signature', 'region', 'school', 'mobile', 'create_time'];
        $data = $query->select($columns)
            ->orderBy('create_time', 'desc')
            ->paginate((int)$page_size, page: (int)$page);
        return paginateTransformer($data);
    }

    public function getApiList(array $params, bool $paginate = true, int $limit = 0)
    {
        $query = $this->buildQuery($params);
        $query->select(['id', 'nickname', 'avatar', 'fans_num'])
            ->orderBy('create_time', 'desc');
        if ($paginate) {
            $page = !empty($params['page']) ? $params['page'] : 1;
            $page_size = !empty($params['page_size']) ? $params['page_size'] : 15;
            $data = $query->paginate((int)$page_size, page: (int)$page);
            $data = paginateTransformer($data);
            if (!empty($data['items'])) {
                $data['items'] = $this->apiListTransformer($data['items'], $params);
            }
        } else {
            if ($limit) {
                $data = $query->limit($limit)->get()->toArray();
            } else {
                $data = $query->get()->toArray();
            }
            $data = $this->apiListTransformer($data, $params);
        }
        return $data;
    }

    protected function apiListTransformer(array $items, array $params = [])
    {
        $u_ids = array_column($items, 'id');
        $follow_ids = $this->followService->getFollowIds($params['current_user_id'] ?? 0, $u_ids);
        foreach ($items as $item) {
            $item->is_follow = in_array($item['id'], $follow_ids) ? 1 : 0;
            $this->objectTransformer($item, [], $params);
        }
        return $items;
    }

    protected function buildQuery(array $params)
    {
        $query = Db::table('user');
        if (!empty($params['username'])) {
            $query->where('username', 'like', '%' . $params['username'] . '%');
        }
        if (!empty($params['nickname'])) {
            $query->where('nickname', 'like', '%' . $params['nickname'] . '%');
        }
        if (!empty($params['keyword'])) {
            $query->where('nickname', 'like', '%' . $params['keyword'] . '%');
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
        if (!empty($params['start_time']) && !empty($params['end_time'])) {
            $query->whereBetween('create_time', [$params['start_time'], $params['end_time']]);
        }
        return $query;
    }

    public function getInfo(int $id, array $cate = ['created_days', 'prestige'], array $params = []): object
    {
        $columns = ['id', 'name', 'username', 'nickname', 'sex', 'avatar', 'bg', 'signature', 'region', 'school', 'mobile', 'create_time', 'fans_num', 'follow_num', 'like_num'];
        $user = Db::table('user')->where(['id' => $id])->select($columns)->first();
        if (!$user) {
            throw new LogicException('用户不存在');
        }
        $this->objectTransformer($user, $cate, $params);
        return $user;
    }

    #[Cacheable(prefix: 'user:info:', ttl: 60)]
    public function getAuthUserInfo(int $id)
    {
        $columns  = ['id', 'username', 'nickname', 'avatar', 'mobile'];
        $user = Db::table('user')->where(['id' => $id])->select($columns)->first();
        return $user;
    }

    protected function objectTransformer(object $item, array $cate = [], array $params = [])
    {
        if (property_exists($item, 'avatar')) {
            $item->avatar_url = getAvatar($item->avatar);
        }
        if (property_exists($item, 'bg')) {
            $item->bg_url = generateFileUrl($item->bg);
        }
        if (in_array('created_days', $cate)) {
            $start = strtotime($item->create_time);
            $end = time();
            $item->created_days = ceil(abs($end - $start) / 86400);
        }
        if (in_array('prestige', $cate) || in_array('coin', $cate)) {
            $credit = $this->creditService->getUserCredit($item->id);
            in_array('prestige', $cate) && $item->prestige = $credit['prestige'] ?? 0;
            in_array('coin', $cate) && $item->coin = $credit['coin'] ?? 0;
        }
        if (in_array('is_follow', $cate)) {
            $item->is_follow = $this->followService->checkIsFollow($params['current_user_id'] ?? 0, $item->id);
        }
    }

    #[Cacheable(prefix: 'user_visit', ttl: 3600)]
    public function addHomeViewRecord(int $visitor_id, int $user_id): bool
    {
        Db::table('user_visit')->insert([
            'visitor_id' => $visitor_id,
            'user_id' => $user_id,
            'create_time' => date('Y-m-d H:i:s')
        ]);
        return true;
    }

    public function getVisitorList(int $user_id, array $params): array
    {
        $page = empty($params['page']) ? 1 : (int)$params['page'];
        $page_size = empty($params['page_size']) ? 15 : (int)$params['page_size'];
        $list = Db::table('user_visit')
            ->leftJoin('user', 'user.id', '=', 'user_visit.visitor_id')
            ->where(['user_visit.user_id' => $user_id])
            ->select(['user.id', 'user.nickname', 'user.avatar'])
            ->orderBy('user_visit.create_time', 'desc')
            ->paginate((int)$page_size, page: (int)$page);
        $list = paginateTransformer($list);
        foreach ($list['items'] as $item) {
            $this->objectTransformer($item, [], $params);
        }
        return $list;
    }

    public function changInfo(int $user_id, array $params)
    {
        $arr = [];
        $user = Db::table('user')->where('id', $user_id)->first();
        $wx_core = Db::table('user_core')
            ->where(['user_id' => $user_id, 'source' => 'wechatmini'])
            ->first();
        $msg = '';
        if (!empty($params['nickname']) && $params['nickname'] != $user->nickname) {
            if (!empty($wx_core) && $this->mini_lib->msgSecCheck($params['nickname'], $wx_core->openid)) {
                return ['code' => 0, 'msg' => '昵称包含敏感词'];
            }
            $arr['nickname'] = $params['nickname'];
        }
        if (!empty($params['signature']) && $params['signature'] != $user->signature) {
            if (!empty($wx_core) && $this->mini_lib->msgSecCheck($params['signature'], $wx_core->openid)) {
                return ['code' => 0, 'msg' => '昵称包含敏感词'];
            }
            $arr['signature'] = $params['signature'];
        }
        if(isset($params['sex']) && in_array($params['sex'], Sex::getKeys())){
            $arr['sex'] = $params['sex'];
        }
        if(!empty($params['region_id']) && $params['region_id'] != $user->region_id){
            $region = Db::table('sys_region')->where('id', $params['region_id'])->value('name');
            if(empty($region)){
                return ['code' => 0, 'msg' => '地区不存在'];
            }
            $arr['region'] = $region;
            $arr['region_id'] = $params['region_id'];
        }
        if(!empty($params['region']) && $params['region'] != $user->region){
            $arr['region'] = $params['region'];
            $arr['region_id'] = 0;
        }
        if(!empty($params['school']) && $params['school'] != $user->school){
            $arr['school'] = $params['school'];
        }
        if (!empty($params['avatar']) && $params['avatar'] != $user->avatar) {
            if (!empty($wx_core)) {
                $check = $this->mediaAuditService->addMediaAudit($user_id, $wx_core->openid, $params['avatar'], 'avatar', $user_id);
                if($check == IsRisky::SAFE->value){
                    $arr['avatar'] = $params['avatar'];
                }elseif ($check == IsRisky::RISKY->value){
                    return ['code' => 0, 'msg' => "头像未通过审核，请更换照片"];
                }else{
                    $msg = '图片审核中';
                }
            } else {
                $arr['avatar'] = $params['avatar'];
            }
        }
        if (!empty($params['bg']) && $params['bg'] != $user->bg) {
            if (!empty($wx_core)) {
                $check = $this->mediaAuditService->addMediaAudit($user_id, $wx_core->openid, $params['bg'], 'bg', $user_id);
                if($check == IsRisky::SAFE->value){
                    $arr['bg'] = $params['bg'];
                }elseif ($check == IsRisky::RISKY->value){
                    return ['code' => 0, 'msg' => "背景图未通过审核，请更换照片"];
                }else{
                    $msg = '图片审核中';
                }
            } else {
                $arr['bg'] = $params['bg'];
            }
        }
        if(empty($arr)){
            if(empty($msg)){
                return ['code' => 0, 'msg' => '没有需要修改的项'];
            }else{
                return ['code' => 1, 'msg' => $msg];
            }
        }
        Db::table('user')->where('id', $user_id)->update($arr);
        return ['code' => 1, 'msg' => empty($msg) ? '修改成功' : $msg];
    }
}