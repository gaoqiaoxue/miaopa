<?php

namespace App\Service;

use App\Constants\AbleStatus;
use App\Constants\CircleRelationType;
use App\Constants\CircleType;
use App\Constants\PostType;
use App\Exception\LogicException;
use App\Exception\ParametersException;
use Hyperf\DbConnection\Db;

class CircleService
{
    public function getSelect($params = [], $field = ['id', 'name'], $limit = 0)
    {
        $query = $this->buildQuery($params);
        $query->select($field)
            ->orderBy('weight', 'desc')
            ->orderBy('id', 'desc');
        if($limit){
            $list = $query->limit($limit)->get()->toArray();
        }else{
            $list = $query->get()->toArray();
        }
        foreach ($list as $item) {
            $this->objectTransformer($item,$params['cate'] ?? [],$params);
        }
        return $list;
    }

    // 圈子列表
    public function getList($params = [], $cate = [])
    {
        if(!empty($params['key'])){
            switch($params['key']){
                case 'follow':
                    $params['is_follow'] = 1; break;
                case 'hot':
                    $params['is_hot'] = 1; break;
                case 'circle':
                    $params['circle_type'] = CircleType::CIRCLE->value;break;
                case 'cartoon':
                    $params['circle_type'] = CircleType::CARTOON->value;break;
                case 'game':
                    $params['circle_type'] = CircleType::GAME->value;break;
            }
        }
        $query = $this->buildQuery($params);
        $page = !empty($params['page']) ? $params['page'] : 1;
        $page_size = !empty($params['page_size']) ? $params['page_size'] : 15;
        $data = $query->select(['id', 'cover', 'name', 'circle_type', 'status', 'follow_count', 'create_time'])
            ->orderBy('is_hot', 'desc')
            ->orderBy('weight', 'desc')
            ->orderBy('create_time', 'desc')
            ->paginate((int)$page_size, page: (int)$page);
        $data = paginateTransformer($data);
        if (!empty($data['items'])) {
            if(in_array('is_follow',$cate)){
                $ids = array_column($data['items'], 'id');
                $follow_ids = Db::table('circle_follow')
                    ->where(['user_id' => $params['user_id'] ?? 0])
                    ->whereIn('circle_id', $ids)
                    ->pluck('circle_id')
                    ->toArray();
                $params['follow_ids'] = $follow_ids;
            }
            foreach ($data['items'] as $item) {
                $this->objectTransformer($item, $cate, $params);
            }
        }
        return $data;
    }

    protected function buildQuery($params)
    {
        $query = Db::table('circle');
        if (isset($params['status']) && in_array($params["status"], AbleStatus::getKeys())) {
            $query->where('status', '=', $params['status']);
        }
        if(!empty($params['relation_type'])){
            $query->where('relation_type', '=', $params['relation_type']);
        }
        if(!empty($params['name'])){
            $query->where('name', 'like', '%' . $params['name'] . '%');
        }
        if(!empty($params['is_follow'])){
            $user_id = $params['user_id'] ?? 0;
            $follow_ids = Db::table('circle_follow')
                ->where(['user_id' => $user_id])
                ->pluck('circle_id')
                ->toArray();
            $query->whereIn('id', $follow_ids);
        }
        if(!empty($params['is_hot'])){
            $query->where('is_hot', '=', 1);
        }
        if (!empty($params['circle_type'])) {
            $query->where('circle_type', $params['circle_type']);
        }
        if (!empty($params['start_time']) && !empty($params['end_time'])) {
            $query->whereBetween('create_time', [$params['start_time'], $params['end_time']]);
        }
        return $query;
    }

    public function getInfo(int $circle_id, array $cate = [], array $params = []): object
    {
        if (empty($circle_id))
            throw new ParametersException('请传入圈子ID');
        $circle = Db::table('circle')
            ->where(['id' => $circle_id])
            ->select(['id', 'bg', 'cover', 'name', 'circle_type', 'weight', 'status', 'is_hot', 'relation_type', 'relation_ids', 'description', 'create_time','create_by','follow_count'])
            ->first();
        if (!$circle) {
            throw new ParametersException('圈子不存在');
        }
        $this->objectTransformer($circle,$cate, $params);
        return $circle;
    }

    protected function getRelations($relation_type, $relation_ids, $source = 'api', $limit =0): array
    {
        if (empty($relation_ids)) {
            return [];
        }
        if ($relation_type == CircleRelationType::CIRCLE->value) {
            $query = Db::table('circle')->whereIn('id', $relation_ids);
            if($source == 'api'){
                $query->where('status', '=', AbleStatus::ENABLE->value);
            }
            if(!empty($limit)){
                $query->limit($limit);
            }
            $list = $query->select(['id', 'name', 'cover'])->get()->toArray();
        } elseif ($relation_type == CircleRelationType::ROLE->value) {
            $query = Db::table('role')->whereIn('id', $relation_ids);
            if($source == 'api'){
                $query->where('status', '=', AbleStatus::ENABLE->value)
                    ->where('audit_status', '=', AbleStatus::ENABLE->value);
            }
            if(!empty($limit)){
                $query->limit($limit);
            }
            $list = $query->select(['id', 'name', 'cover'])->get()->toArray();
        } else {
            return [];
        }
        foreach ($list as $item){
            $item->cover_url = generateFileUrl($item->cover);
        }
        return $list;
    }

    public function add(array $data): int
    {
        return Db::table('circle')->insertGetId($this->generalData($data, true));
    }

    public function edit(array $data): int
    {
        $circle_id = (int)$data['circle_id'];
        unset($data['circle_id']);
        return Db::table('circle')
            ->where(['id' => $circle_id])
            ->update($this->generalData($data));
    }

    protected function generalData(array $data, $is_add = false): array
    {
        $result = [
            'bg' => $data['bg'],
            'cover' => $data['cover'],
            'name' => $data['name'],
            'circle_type' => $data['circle_type'],
            'status' => $data['status'],
            'is_hot' => $data['is_hot'],
            'weight' => $data['weight'] ?? 100,
            'relation_type' => !empty($data['relation_type']) ? $data['relation_type'] : null,
            'relation_ids' => json_encode(!empty($data['relation_ids']) ? $data['relation_ids'] : []),
            'description' => $data['description'],
            'update_time' => date('Y-m-d H:i:s'),
        ];
        if ($is_add) {
            $result['create_by'] = $data['create_by'] ?: 0;
            $result['create_time'] = date('Y-m-d H:i:s');
        }
        return $result;
    }

    public function changeStatus(int $circle_id, int $status): int
    {
        return Db::table('circle')
            ->where(['id' => $circle_id])
            ->update(['status' => $status]);
    }

    public function getFollowUsers(array $params): array
    {
        $query = Db::table('circle_follow')
            ->leftJoin('user', 'user.id', '=', 'circle_follow.user_id')
            ->where('circle_id', $params['circle_id']);
        if (!empty($params['user_id'])) {
            $query->where('circle_follow.user_id', $params['user_id']);
        }
        if (!empty($params['nickname'])) {
            $query->where('user.nickname', 'like', '%' . $params['nickname'] . '%');
        }
        if (!empty($params['sex'])) {
            $query->where('user.sex', '=', $params['sex']);
        }
        if (!empty($params['start_time']) && !empty($params['end_time'])) {
            $query->whereBetween('circle_follow.create_time', [$params['start_time'], $params['end_time']]);
        }
        $page = !empty($params['page']) ? $params['page'] : 1;
        $page_size = !empty($params['page_size']) ? $params['page_size'] : 15;
        $data = $query->select(['circle_follow.circle_id', 'circle_follow.user_id', 'circle_follow.create_time', 'user.nickname', 'user.sex', 'user.mobile', 'user.school', 'user.region'])
            ->orderBy('circle_follow.create_time', 'desc')
            ->paginate((int)$page_size, page: (int)$page);
        $data = paginateTransformer($data);
        return $data;
    }

    // 首页推荐圈子 关注优先
    public function getRecommendList(int $user_id = 0): array
    {
        $total_num = 8;
        if (!empty($user_id)) {
            $sql = 'SELECT 
    c.id, c.name, c.cover, c.relation_type, c.relation_ids,
    CASE WHEN cf.user_id IS NOT NULL THEN 1 ELSE 0 END AS is_follow
FROM 
    mp_circle c
LEFT JOIN 
    mp_circle_follow cf ON c.id = cf.circle_id AND cf.user_id = :userId
WHERE 
    c.status = :status
ORDER BY 
    is_follow DESC, 
    c.is_hot DESC, 
    c.weight DESC, 
    c.id DESC
LIMIT :limit;';
            $circles = Db::select($sql, ['userId' => 1, 'status' => AbleStatus::ENABLE->value, 'limit' => $total_num]);
        }else{
            $circles = Db::table('circle')
                ->where('status', '=', AbleStatus::ENABLE)
                ->selectRaw('id, name, cover, relation_type, relation_ids,0 as is_follow')
                ->orderBy('is_hot', 'desc')
                ->orderBy('weight', 'desc')
                ->orderBy('id', 'desc')
                ->limit($total_num)
                ->get()
                ->toArray();
        }
        if (!empty($circles)) {
            foreach ($circles as $circle) {
                $this->objectTransformer($circle);
            }
        }
        return $circles;
    }

    // 圈子关联列表
    public function getRelationsById(int $circle_id): array
    {
        $circle = Db::table('circle')
            ->where(['id' => $circle_id])
            ->select(['id', 'name', 'relation_type', 'relation_ids'])
            ->first();
        if (empty($circle)) {
            throw new ParametersException('圈子不存在');
        }
        $relations = $this->getRelations($circle->relation_type, json_decode($circle->relation_ids, true));
        if (!empty($relations)) {
            foreach ($relations as $relation) {
                $relation->cover_url = generateFileUrl($relation->cover);
            }
        }
        return $relations;
    }

    // API 按分类分组 带搜索和关注
    public function getAllByType(int $user_id = 0, string $keyword = '', bool $has_follow = true, int $limit = 0)
    {
        $query = Db::table('circle')
            ->where('status', '=', AbleStatus::ENABLE)
            ->orderBy('is_hot', 'desc')
            ->orderBy('weight', 'desc')
            ->orderBy('id', 'desc')
            ->select(['id', 'name', 'cover', 'circle_type', 'is_hot']);
        if (!empty($keyword)) {
            $query->where('name', 'like', '%' . $keyword . '%');
        }
        $all = $query->get()->toArray();
        $follow_ids = [];
        if (!empty($user_id) && $has_follow) {
            $follow_ids = Db::table('circle_follow')
                ->where('user_id', '=', $user_id)
                ->pluck('circle_id')
                ->toArray();
        }
        $result = [
            'follow' => ['title' => '关注', 'key' => 'follow', 'children' => [], 'total' => 0],
            'hot' => ['title' => '热门', 'key' => 'hot', 'children' => [], 'total' => 0],
            CircleType::CIRCLE->name => ['title' => CircleType::CIRCLE->getMessage(), 'key' => 'circle', 'children' => [], 'total' => 0],
            CircleType::CARTOON->name => ['title' => CircleType::CARTOON->getMessage(), 'key' => 'cartoon', 'children' => [], 'total' => 0],
            CircleType::GAME->name => ['title' => CircleType::GAME->getMessage(), 'key' => 'game', 'children' => [], 'total' => 0]
        ];
        if(!$has_follow){
            unset($result['follow']);
        }
        foreach ($all as $circle) {
            $this->objectTransformer($circle);
            if ($has_follow && in_array($circle->id, $follow_ids)) {
                $result['follow']['total'] += 1;
                ($limit == 0 || count($result['follow']['children']) < $limit) && $result['follow']['children'][] = $circle;
            }
            if ($circle->is_hot) {
                $result['hot']['total'] += 1;
                ($limit == 0 || count($result['hot']['children']) < $limit) && $result['hot']['children'][] = $circle;
            }
            $type_name = CircleType::tryFrom($circle->circle_type)->name ?? '';
            $result[$type_name]['total'] += 1;
            ($limit == 0 || count($result[$type_name]['children']) < $limit) && $result[$type_name]['children'][] = $circle;
        }
        return array_values($result);
    }

    public function follow(int $user_id, int $circle_id, int $status): bool
    {
        $has = Db::table('circle_follow')
            ->where(['circle_id' => $circle_id, 'user_id' => $user_id])
            ->count();
        if ((empty($has) && $status == 0) || (!empty($has) && $status == 1)) {
            return true;
        }
        Db::beginTransaction();
        try {
            if ($status == 1) {
                $res1 = Db::table('circle_follow')->insert([
                    'circle_id' => $circle_id,
                    'user_id' => $user_id,
                    'create_time' => date('Y-m-d H:i:s'),
                ]);
                $res2 = Db::table('circle')->where('id', $circle_id)->increment('follow_count', 1);
            } else {
                $res1 = Db::table('circle_follow')
                    ->where(['circle_id' => $circle_id, 'user_id' => $user_id])
                    ->delete();
                $res2 = Db::table('circle')->where('id', $circle_id)->decrement('follow_count', 1);
            }
            if(!$res1 || !$res2){
                throw new LogicException('操作失败');
            }
            Db::commit();
        }catch ( \Throwable $ex){
            Db::rollBack();
            throw new ParametersException($ex->getMessage());
        }
        return true;
    }

    protected function checkIsFollow(int $circle_id, int $user_id): int
    {
        if(empty($user_id) || empty($circle_id)){
            return 0;
        }
        $is_follow = Db::table('circle_follow')
            ->where(['circle_id' => $circle_id, 'user_id' => $user_id])
            ->count();
        return $is_follow ? 1 : 0;
    }

    protected function objectTransformer(object $item, array $cate = [], array $params = [])
    {
        if (property_exists($item, 'cover')) {
            $item->cover_url = generateFileUrl($item->cover);
        }
        if (property_exists($item, 'bg')) {
            $item->bg_url = generateFileUrl($item->bg);
        }
        if (in_array('is_follow', $cate)) {
            if (isset($params['follow_ids'])) {
                $item->is_follow = in_array($item->id, $params['follow_ids']) ? 1 : 0;
            } else {
                $item->is_follow = $this->checkIsFollow($item->id, $params['user_id'] ?? 0);
            }
        }
        if(in_array('admin_relations', $cate)){
            $item->relations = $this->getRelations($item->relation_type, json_decode($item->relation_ids, true, 'admin'));
        }
        if(in_array('relations', $cate)){
            $item->relations = $this->getRelations($item->relation_type, json_decode($item->relation_ids, true, 'api', 20));
        }
        if(in_array('post_count', $cate)){
            $post_counts = Db::table('post')
                ->where('circle_id', $item->id)
                ->groupBy('post_type')
                ->select(['post_type', Db::raw('count(*) as count')])
                ->get()
                ->toArray();
            $post_counts = array_column($post_counts, 'count', 'post_type');
            $item->dynamic_post_count = $post_counts[PostType::DYNAMIC->value] ?? 0;
            $item->question_post_count = $post_counts[PostType::QA->value] ?? 0;
        }
        if(in_array('creater',$cate)){
            $item->creater_name = Db::table('sys_user')
                ->where('user_id', '=', $item->create_by)
                ->value('nick_name');
        }
    }
}