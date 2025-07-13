<?php

namespace App\Service;

use App\Constants\CabinetType;
use App\Exception\LogicException;
use Hyperf\DbConnection\Db;
use Hyperf\Di\Annotation\Inject;

class CabinetService
{

    public function getList(array $params): array
    {
        $query = Db::table('cabinet');
        if (!empty($params['user_id'])) {
            $query->where('user_id', $params['user_id']);
        }
        if(isset($params['is_public'])){
            $query->where('is_public', $params['is_public']);
        }
        $page = !empty($params['page']) ? $params['page'] : 1;
        $page_size = !empty($params['page_size']) ? $params['page_size'] : 15;
        $columns = ['id', 'name', 'user_id', 'cover', 'item_num', 'cabinet_type','is_public', 'create_time'];
        $data = $query->select($columns)
            ->orderBy('create_time', 'desc')
            ->paginate((int)$page_size, page: (int)$page);
        $list = paginateTransformer($data);
        $list['items'] = array_map(function ($item) {
            $item->cover = generateFileUrl($item->cover);
            return $item;
        }, $list['items']);
        return $list;
    }

    public function getInfo(int $cabinet_id): object
    {
        $info = Db::table('cabinet')
            ->where('id', $cabinet_id)
            ->select(['id', 'name', 'user_id', 'cover', 'item_num', 'cabinet_type', 'is_public', 'create_time'])
            ->first();
        if (empty($info)) {
            throw new LogicException('次元柜不存在');
        }
        $info->cabinet_type_name = CabinetType::from($info->cabinet_type)->name;
        $info->cover_url = generateFileUrl($info->cover);
        return $info;
    }

    public function add(int $user_id, array $params): int
    {
        return Db::table('cabinet')->insertGetId([
            'user_id' => $user_id,
            'name' => $params['name'],
            'cover' => $params['cover'],
            'item_num' => 0,
            'cabinet_type' => $params['cabinet_type'],
            'is_public' => $params['is_public'],
            'create_time' => date('Y-m-d H:i:s'),
            'update_time' => date('Y-m-d H:i:s'),
        ]);
    }

    public function edit(int $user_id, array $params): bool
    {
        $info = Db::table('cabinet')->where('id', $params['cabinet_id'])->first();
        if (!$info) {
            throw new LogicException('次元柜不存在');
        }
        if ($info->user_id != $user_id) {
            throw new LogicException('您没有权限编辑此次元柜');
        }
        return Db::table('cabinet')->where('id', $params['cabinet_id'])->update([
            'name' => $params['name'],
            'cover' => $params['cover'],
            'is_public' => $params['is_public'],
            'cabinet_type' => $params['cabinet_type'],
            'update_time' => date('Y-m-d H:i:s'),
        ]);
    }

    public function delete(int $cabinet_id, int $user_id)
    {
        return Db::table('cabinet')
            ->where('id', $cabinet_id)
            ->where('user_id', $user_id)
            ->delete();
    }

    public function getItemList(array $params)
    {
        $query = Db::table('cabinet_item')
            ->where('cabinet_id', $params['cabinet_id']);
        $page = !empty($params['page']) ? $params['page'] : 1;
        $page_size = !empty($params['page_size']) ? $params['page_size'] : 15;
        $data = $query->select(['id', 'name', 'alias', 'cover', 'number', 'create_time'])
            ->orderBy('create_time', 'desc')
            ->paginate((int)$page_size, page: (int)$page);
        $list = paginateTransformer($data);
        if (!empty($list['items'])) {
            foreach ($list['items'] as $item) {
                $item->cover_url = generateFileUrl($item->cover);
            }
        }
        return $list;
    }

    public function getItemInfo(int $item_id): object
    {
        $info = Db::table('cabinet_item')
            ->where('id', $item_id)
            ->select(['id', 'name', 'alias', 'images', 'number', 'create_time'])
            ->first();
        if (empty($info)) {
            throw new LogicException('物品不存在');
        }
        $images = generateMulFileUrl($info->images);
        $info->images = array_values($images);
        return $info;
    }

    public function addItem(array $params): int
    {
        Db::beginTransaction();
        try {
            $insert_id = Db::table('cabinet_item')->insertGetId([
                'cabinet_id' => $params['cabinet_id'],
                'name' => $params['name'],
                'alias' => $params['alias'],
                'cover' => $params['images'][0] ?? 0,
                'images' => implode(',', $params['images']),
                'number' => $params['number'],
                'create_time' => date('Y-m-d H:i:s'),
                'update_time' => date('Y-m-d H:i:s'),
            ]);
            Db::table('cabinet')
                ->where('id', $params['cabinet_id'])
                ->increment('item_num', $params['number']);
            Db::commit();
        } catch (\Throwable $ex) {
            Db::rollBack();
            throw new LogicException($ex->getMessage());
        }
        return $insert_id;
    }

    public function editItem(array $params): bool
    {
        $item = Db::table('cabinet_item')
            ->where('id', $params['item_id'])
            ->first();
        if (empty($item)) {
            throw new LogicException('藏品不存在');
        }
        Db::beginTransaction();
        try {
            $update = [
                'name' => $params['name'],
                'alias' => $params['alias'],
                'cover' => $params['images'][0] ?? 0,
                'images' => implode(',', $params['images']),
                'number' => $params['number'],
                'update_time' => date('Y-m-d H:i:s'),
            ];
            Db::table('cabinet_item')->where('id', $params['item_id'])->update($update);
            if($item->number != $params['number']){
                Db::table('cabinet')
                    ->where('id', $item->cabinet_id)
                    ->decrement('item_num', $item->number - $params['number']);
            }
            Db::commit();
        } catch (\Throwable $ex) {
            Db::rollBack();
            throw new LogicException($ex->getMessage());
        }
        return true;
    }

    public function deleteItem(int $item_id): bool
    {
        $item = Db::table('cabinet_item')
            ->where('id', $item_id)
            ->first();
        if (empty($item)) {
            throw new LogicException('藏品不存在');
        }
        Db::beginTransaction();
        try {
            Db::table('cabinet_item')->where('id', $item_id)->delete();
            Db::table('cabinet')
                ->where('id', $item->cabinet_id)
                ->decrement('item_num', $item->number);
            Db::commit();
        } catch (\Throwable $ex) {
            Db::rollBack();
            throw new LogicException($ex->getMessage());
        }
        return true;
    }

}