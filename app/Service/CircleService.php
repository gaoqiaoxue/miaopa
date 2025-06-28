<?php

namespace App\Service;

use App\Constants\CircleStatus;
use App\Exception\ParametersException;
use Hyperf\Collection\Collection;
use Hyperf\DbConnection\Db;

class CircleService
{
    public function getList($params = [])
    {
        $query = Db::table('circle');
        if (!empty($params['name'])) {
            $query->where('name', 'like', '%' . $params['name'] . '%');
        }
        if (!empty($params['circle_type'])) {
            $query->where('circle_type', $params['circle_type']);
        }
        if (isset($params['status']) && in_array($params["status"], CircleStatus::cases())) {
            $query->where('status', '=', $params['status']);
        }
        if (!empty($params['create_time_start']) && !empty($params['create_time_end'])) {
            $query->whereBetween('create_time', [$params['create_time_start'], $params['create_time_end']]);
        }
        $page = !empty($params['page']) ? $params['page'] : 1;
        $page_size = !empty($params['page_size']) ? $params['page_size'] : 15;
        $data = $query->select(['id', 'cover', 'name', 'circle_type', 'status', 'follow_count', 'create_time'])
            ->orderBy('create_time', 'desc')
            ->paginate((int)$page_size, page: (int)$page);
        $data = paginateTransformer($data);
        if (!empty($data['items'])) {
            $covers = array_column($data['items'], 'cover');
            $covers = FileService::getFilepathByIds($covers);
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
        $circle->bg_url = FileService::getFilePathById($circle->bg);
        $circle->cover_url = FileService::getFilePathById($circle->cover);
        $circle->relations = $this->getRelations($circle->relation_type, json_decode($circle->relation_ids, true));
        return $circle;
    }

    protected function getRelations($relation_type, $relation_ids): array
    {
        if (empty($relation_ids)) {
            return [];
        }
        if ($relation_type == 'circle') {
            return Db::table('circle')
                ->whereIn('id', $relation_ids)
                ->select(['id', 'name'])
                ->get()
                ->toArray();
        } else {
            return Db::table('role')
                ->whereIn('id', $relation_ids)
                ->select(['id', 'name'])
                ->get()
                ->toArray();
        }
    }

    public function add(array $data): int
    {
        return Db::table('circle')->insertGetId([
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
            'create_time' => date('Y-m-d H:i:s'),
            'update_time' => date('Y-m-d H:i:s'),
        ]);
    }

    public function edit(array $data): int
    {
        $circle_id = (int) $data['circle_id'];
        unset($data['circle_id']);
        return Db::table('circle')
            ->where(['id' => $circle_id])
            ->update([
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
            ]);
    }

    public function changeStatus(int $circle_id, int $status): int
    {
        return Db::table('circle')
            ->where(['id' => $circle_id])
            ->update(['status' => $status]);
    }

}