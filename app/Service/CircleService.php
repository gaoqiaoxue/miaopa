<?php

namespace App\Service;

use App\Constants\AbleStatus;
use App\Constants\CircleRelationType;
use App\Constants\PostType;
use App\Exception\ParametersException;
use Hyperf\DbConnection\Db;
use Hyperf\Di\Annotation\Inject;

class CircleService
{
    #[Inject]
    protected FileService $fileService;

    public function getList($params = [])
    {
        $query = Db::table('circle');
        if (!empty($params['name'])) {
            $query->where('name', 'like', '%' . $params['name'] . '%');
        }
        if (!empty($params['circle_type'])) {
            $query->where('circle_type', $params['circle_type']);
        }
        if (isset($params['status']) && in_array($params["status"], AbleStatus::cases())) {
            $query->where('status', '=', $params['status']);
        }
        if (!empty($params['start_time']) && !empty($params['end_time'])) {
            $query->whereBetween('create_time', [$params['start_time'], $params['end_time']]);
        }
        $page = !empty($params['page']) ? $params['page'] : 1;
        $page_size = !empty($params['page_size']) ? $params['page_size'] : 15;
        $data = $query->select(['id', 'cover', 'name', 'circle_type', 'status', 'follow_count', 'create_time'])
            ->orderBy('create_time', 'desc')
            ->paginate((int)$page_size, page: (int)$page);
        $data = paginateTransformer($data);
        if (!empty($data['items'])) {
            $covers = array_column($data['items'], 'cover');
            $covers = $this->fileService->getFilepathByIds($covers);
            foreach ($data['items'] as $item) {
                $item->cover_url = $covers[$item->cover] ?? '';
            }
        }
        return $data;
    }

    public function getInfo(int $circle_id): object
    {
        if (empty($circle_id))
            throw new ParametersException('请传入圈子ID');
        $circle = Db::table('circle')
            ->where(['id' => $circle_id])
            ->select(['id', 'bg', 'cover', 'name', 'circle_type', 'status', 'is_hot', 'relation_type', 'relation_ids', 'description', 'create_time'])
            ->first();
        if (!$circle) {
            throw new ParametersException('圈子不存在');
        }
        $circle->bg_url = $this->fileService->getFilePathById($circle->bg);
        $circle->cover_url = $this->fileService->getFilePathById($circle->cover);
        $circle->relations = $this->getRelations($circle->relation_type, json_decode($circle->relation_ids, true));

        $post_counts = Db::table('post')
            ->where('circle_id', $circle_id)
            ->groupBy('post_type')
            ->select(['post_type', Db::raw('count(*) as count')])
            ->get()
            ->toArray();
        $post_counts = array_column($post_counts, 'count', 'post_type');
        $circle->dynamic_post_count = $post_counts[PostType::DYNAMIC->value] ?? 0;
        $circle->question_post_count = $post_counts[PostType::QA->value] ?? 0;
        return $circle;
    }

    protected function getRelations($relation_type, $relation_ids): array
    {
        if (empty($relation_ids)) {
            return [];
        }
        if ($relation_type == CircleRelationType::CIRCLE) {
            return Db::table('circle')
                ->whereIn('id', $relation_ids)
                ->select(['id', 'name'])
                ->get()
                ->toArray();
        } elseif ($relation_type == CircleRelationType::ROLE) {
            return Db::table('role')
                ->whereIn('id', $relation_ids)
                ->select(['id', 'name'])
                ->get()
                ->toArray();
        } else {
            return [];
        }
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

}