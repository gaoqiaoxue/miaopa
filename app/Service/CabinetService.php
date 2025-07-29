<?php

namespace App\Service;

use App\Constants\CabinetType;
use App\Exception\LogicException;
use Hyperf\DbConnection\Db;

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
            $item->cabinet_type_name = CabinetType::from($item->cabinet_type)->getMessage();
            $item->cover_url = generateFileUrl($item->cover);
            $virtual_items = $this->getItemList(['cabinet_id' => $item->id, 'page_size' => 4]);
            $item->virtual_items = $virtual_items['items'];
            $item->item_count = $virtual_items['total'];
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
        $info->cabinet_type_name = CabinetType::from($info->cabinet_type)->getMessage();
        $info->cover_url = generateFileUrl($info->cover);
        return $info;
    }

    public function add(int $user_id, array $params): array
    {
        $data = [
            'user_id' => $user_id,
            'name' => $params['name'],
            'cover' => $params['cover'] ?? '',
            'item_num' => 0,
            'cabinet_type' => $params['cabinet_type'],
            'is_public' => $params['is_public'],
            'create_time' => date('Y-m-d H:i:s'),
            'update_time' => date('Y-m-d H:i:s'),
        ];
        $id = Db::table('cabinet')->insertGetId($data);
        $data['cabinet_type_name'] = CabinetType::from($data['cabinet_type'])->getMessage();
        $data['id'] = $id;
        return $data;
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
            'cover' => $params['cover'] ?? '',
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

    public function getItemList(array $params, bool $is_paginate = true, int $limit = 4)
    {
        $query = Db::table('cabinet_item')
            ->where('cabinet_id', $params['cabinet_id'])
            ->select(['id', 'name', 'alias', 'cover', 'number', 'create_time'])
            ->orderBy('create_time', 'desc');
        if($is_paginate){
            $page = !empty($params['page']) ? $params['page'] : 1;
            $page_size = !empty($params['page_size']) ? $params['page_size'] : 15;
            $data = $query->paginate((int)$page_size, page: (int)$page);
            $list = paginateTransformer($data);
        }else{
            $data = $query->limit($limit)->get()->toArray();
            $list['items'] = $data;
        }
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
        $info->images = generateMulFileUrl($info->images);
        return $info;
    }

    public function addItem(array $params): array
    {
        Db::beginTransaction();
        try {
            $item_data = [
                'cabinet_id' => $params['cabinet_id'],
                'name' => $params['name'],
                'alias' => $params['alias'],
                'cover' => $params['images'][0] ?? 0,
                'images' => implode(',', $params['images']),
                'number' => $params['number'],
                'create_time' => date('Y-m-d H:i:s'),
                'update_time' => date('Y-m-d H:i:s'),
            ];
            $insert_id = Db::table('cabinet_item')->insertGetId($item_data);
            $item_data['cover_url'] = generateFileUrl($item_data['cover']);
            $item_data['id'] = $insert_id;
            Db::table('cabinet')
                ->where('id', $params['cabinet_id'])
                ->increment('item_num', $params['number']);
            Db::commit();
        } catch (\Throwable $ex) {
            Db::rollBack();
            throw new LogicException($ex->getMessage());
        }
        return $item_data;
    }

    public function editItem(array $params): array
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
        $update['cover_url'] = generateFileUrl($update['cover']);
        $update['id'] = $params['item_id'];
        return $update;
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