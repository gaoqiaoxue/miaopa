<?php

namespace App\Service;

use App\Constants\Sex;
use App\Exception\LogicException;
use App\Library\Contract\AuthTokenInterface;
use Hyperf\DbConnection\Db;
use Hyperf\Di\Annotation\Inject;

class UserService
{
    #[Inject]
    protected CreditService $creditService;

    #[Inject]
    protected UserFollowService $followService;

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
        $query->select(['id','nickname', 'avatar', 'fans_num'])
            ->orderBy('create_time', 'desc');
        if($paginate){
            $page = !empty($params['page']) ? $params['page'] : 1;
            $page_size = !empty($params['page_size']) ? $params['page_size'] : 15;
            $data = $query->paginate((int)$page_size, page: (int)$page);
            $data = paginateTransformer($data);
            if (!empty($data['items'])) {
                $data['items'] = $this->apiListTransformer($data['items'], $params);
            }
        }else{
            if($limit){
                $data = $query->limit($limit)->get()->toArray();
            }else{
                $data = $query->get()->toArray();
            }
            $data = $this->apiListTransformer($data, $params);
        }
        return $data;
    }

    protected function apiListTransformer(array $items, array $params = [])
    {
        $u_ids = array_column($items, 'id');
        $follow_ids = $this->followService->getFollowIds($params['user_id'], $u_ids);
        foreach ($items as $item) {
            $item->is_follow = in_array($item['id'], $follow_ids) ? 1 : 0;
            $this->objectTransformer($item,[],$params);
        }
        return $items;
    }

    protected function buildQuery(array $params){
        $query = Db::table('user');
        if (!empty($params['username'])) {
            $query->where('username', 'like', '%' . $params['username'] . '%');
        }
        if (!empty($params['nickname'])) {
            $query->where('nickname', 'like', '%' . $params['nickname'] . '%');
        }
        if(!empty($params['keyword'])){
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
        if(!empty($params['start_time']) && !empty($params['end_time'])){
            $query->whereBetween('create_time', [$params['start_time'], $params['end_time']]);
        }
        return $query;
    }

    public function getInfo(int $id): object
    {
        $columns = ['id', 'name', 'username', 'nickname', 'sex', 'avatar', 'signature', 'region', 'school', 'mobile', 'create_time'];
        $user = Db::table('user')->where(['id' => $id])->select($columns)->first();
        if (!$user) {
            throw new LogicException('用户不存在');
        }
        $this->objectTransformer($user,['created_days', 'prestige']);
        return $user;
    }

    protected function objectTransformer(object $item, array $cate = [], array $params = [])
    {
        if(property_exists($item, 'avatar')) {
            $item->avatar_url = getAvatar($item->avatar);
        }
        if(in_array('created_days', $cate)){
            $start = strtotime($item->create_time);
            $end = time();
            $item->created_days =  ceil(abs($end - $start) / 86400);
        }
        if(in_array('prestige', $cate) || in_array('coin', $cate)){
            $credit = $this->creditService->getUserCredit($item->id);
            in_array('prestige', $cate) && $item->prestige = $credit['prestige'] ?? 0;
            in_array('coin', $cate) && $item->coin = $credit['coin'] ?? 0;
        }
        if(in_array('is_follow', $cate)){
            $item->is_follow = $this->followService->checkIsFollow($params['user_id'] ?? 0, $item->user_id);
        }
    }
}