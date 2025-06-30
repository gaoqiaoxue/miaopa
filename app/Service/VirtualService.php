<?php

namespace App\Service;

use App\Exception\LogicException;
use Hyperf\DbConnection\Db;
use Hyperf\Di\Annotation\Inject;

class VirtualService
{
    #[Inject]
    protected FileService $fileService;
    public function getList(array $params): array
    {
        $query = Db::table('virtual_item')->where('del_flag', 0);
        if (!empty($params['name'])) {
            $query->where('name', 'like', '%' . $params['name'] . '%');
        }
        if (!empty($params['item_type'])) {
            $query->where('item_type', '=', $params['item_type']);
        }
        $page = !empty($params['page']) ? $params['page'] : 1;
        $page_size = !empty($params['page_size']) ? $params['page_size'] : 15;
        $data = $query->select(['id', 'name', 'item_type', 'exchange_amount', 'valid_days', 'quantity', 'image', 'create_time'])
            ->orderBy('create_time', 'desc')
            ->paginate((int)$page_size, page: (int)$page);
        $data = paginateTransformer($data);
        if (!empty($data['items'])) {
            $images = array_column($data['items'], 'image');
            $images = $this->fileService->getFilepathByIds($images);
            foreach ($data['items'] as $item) {
                $item->image_url = $images[$item->image] ?? '';
            }
        }
        return $data;
    }

    public function getInfo(int $virtual_id): object
    {
        $virtual = Db::table('virtual_item')
            ->where(['id' => $virtual_id, 'del_flag' => 0])
            ->select(['id', 'name', 'item_type', 'exchange_amount', 'valid_days', 'quantity', 'image', 'avatar', 'create_time'])
            ->first();
        if (!$virtual) {
            throw new LogicException('虚拟商品不存在');
        }
        $virtual->image_url = $this->fileService->getFilePathById($virtual->image);
        if (empty($virtual->avatar)) {
            $virtual->avatar_url = '';
        } else {
            $virtual->avatar_url = $this->fileService->getFilePathById($virtual->avatar);
        }
        return $virtual;
    }

    public function add(array $data): int
    {
        $create_data = $this->generalData($data, true);
        if($create_data['is_default'] == 1){
            Db::table('virtual_item')
                ->where('is_default', '=', 1)
                ->where('del_flag', '=', 0)
                ->update(['is_default' => 0]);
        }
        return Db::table('virtual_item')->insertGetId($create_data);
    }

    public function edit(array $data): int
    {
        $update = $this->generalData($data);
        if($update['is_default'] == 1){
            Db::table('virtual_item')
                ->where('is_default', '=', 1)
                ->where('del_flag', '=', 0)
                ->update(['is_default' => 0]);
        }
        return Db::table('virtual_item')
            ->where(['id' => $data['virtual_id']])
            ->update($update);
    }

    protected function generalData(array $data, bool $is_add = false): array
    {
        $result = [
            'name' => $data['name'],
            'item_type' => $data['item_type'],
            'is_default' => $data['is_default'],
            'exchange_amount' => $data['exchange_amount'],
            'valid_days' => $data['valid_days'],
            'quantity' => $data['quantity'],
            'image' => $data['image'],
            'avatar' => $data['avatar'] ?? 0,
            'update_time' => date('Y-m-d H:i:s')
        ];
        if ($is_add) {
            $result['create_by'] = $data['create_by'] ?? 0;
            $result['create_time'] = date('Y-m-d H:i:s');
        }
        return $result;
    }

    public function delete(int $virtual_id)
    {
        return Db::table('virtual_item')->where(['id' => $virtual_id])->update([
            'del_flag' => 1,
            'update_time' => date('Y-m-d H:i:s')
        ]);
    }
}