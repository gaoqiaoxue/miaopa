<?php

namespace App\Service;

use App\Constants\CoinCate;
use App\Constants\VirtualType;
use App\Exception\LogicException;
use DateTime;
use Hyperf\Cache\Annotation\Cacheable;
use Hyperf\Cache\Annotation\CacheEvict;
use Hyperf\DbConnection\Db;
use Hyperf\Di\Annotation\Inject;

class VirtualService
{
    #[Inject]
    protected CreditService $creditService;

    public function getList(array $params): array
    {
        $query = Db::table('virtual_item')->where('del_flag', 0);
        if (!empty($params['name'])) {
            $query->where('name', 'like', '%' . $params['name'] . '%');
        }
        if (!empty($params['item_type'])) {
            $query->where('item_type', '=', $params['item_type']);
        }
        if (isset($params['status'])) {
            $query->where('status', '=', $params['status']);
        }
        if (isset($params['is_default'])) {
            $query->where('is_default', '=', $params['is_default']);
        }
        $page = !empty($params['page']) ? $params['page'] : 1;
        $page_size = !empty($params['page_size']) ? $params['page_size'] : 15;
        $data = $query->select(['id', 'name', 'item_type', 'exchange_amount', 'valid_days', 'quantity', 'image', 'avatar', 'create_time'])
            ->orderBy('weight', 'desc')
            ->orderBy('create_time', 'desc')
            ->paginate((int)$page_size, page: (int)$page);
        $data = paginateTransformer($data);
        $check = !empty($params['check_exchange']) ? true : false;
        $has_ids = [];
        if (!empty($params['current_user_id']) && !empty($params['check_exchange'])) {
            $has_ids = Db::table('virtual_exchange')
                ->where('user_id', $params['current_user_id'])
                ->where('status', 1)
                ->where('valid_time', '>', time())
                ->pluck('item_id')
                ->toArray();
            var_dump($has_ids);
        }
        if (!empty($data['items'])) {
            foreach ($data['items'] as $item) {
                $item->image_url = generateFileUrl($item->image);
                $item->avatar_url = generateFileUrl($item->avatar);
                if ($check) {
                    $item->can_exchange = in_array($item->id, $has_ids) ? 0 : 1;
                }
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
        $virtual->image_url = generateFileUrl($virtual->image);
        $virtual->avatar_url = generateFileUrl($virtual->avatar);
        return $virtual;
    }

    #[CacheEvict(prefix: 'default_avatar_icon', value: '')]
    public function add(array $data): int
    {
        $create_data = $this->generalData($data, true);
        if ($create_data['is_default'] == 1) {
            Db::table('virtual_item')
                ->where('is_default', '=', 1)
                ->update(['is_default' => 0]);
        }
        return Db::table('virtual_item')->insertGetId($create_data);
    }

    #[CacheEvict(prefix: 'default_avatar_icon', value: '')]
    public function edit(array $data): int
    {
        $update = $this->generalData($data);
        if ($update['is_default'] == 1) {
            Db::table('virtual_item')
                ->where('is_default', '=', 1)
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
            'is_default' => $data['item_type'] == VirtualType::MEDAL->value ? 0 : $data['is_default'] ?? 0,
            'exchange_amount' => $data['exchange_amount'],
            'valid_days' => $data['valid_days'],
            'quantity' => $data['quantity'],
            'image' => $data['image'],
            'avatar' => $data['avatar'] ?? '',
            'status' => $data['status'] ?? 1,
            'update_time' => date('Y-m-d H:i:s')
        ];
        if ($is_add) {
            $result['create_by'] = $data['create_by'] ?? 0;
            $result['create_time'] = date('Y-m-d H:i:s');
        }
        return $result;
    }

    public function delete(int $virtual_id): int
    {
        $is_default = Db::table('virtual_item')->where(['id' => $virtual_id])->value('is_default');
        if ($is_default) {
            throw new LogicException('默认形象不能删除');
        }
        return Db::table('virtual_item')->where(['id' => $virtual_id])->update([
            'del_flag' => 1,
            'update_time' => date('Y-m-d H:i:s')
        ]);
    }

    // 获取当前用设置的形象
    public function getCurrent(int $user_id): array
    {
        $list = Db::table('virtual_exchange')
            ->where('user_id', $user_id)
            ->where('is_active', 1)
            ->where('valid_time', '>', time())
            ->select(['item_id', 'name', 'item_type', 'image', 'avatar', 'valid_time'])
            ->orderBy('active_time', 'asc')
            ->get()
            ->toArray();
        $result = [
            'figure' => [],
            'medal' => [],
        ];
        foreach ($list as $item) {
            $item->image_url = generateFileUrl($item->image);
            $item->avatar_url = generateFileUrl($item->avatar);
            if ($item->item_type == VirtualType::FIGURE->value) {
                $result['figure'] = $item;
            } elseif ($item->item_type == VirtualType::MEDAL->value) {
                $result['medal'][] = $item;
            }
        }
        if (empty($result['figure'])) {
            $figure = Db::table('virtual_item')
                ->where('is_default', '=', 1)
                ->where('del_flag', '=', 0)
                ->select(['id as item_id', 'name', 'item_type', 'image', 'avatar', 'valid_days'])
                ->first();
            if (!empty($figure)) {
                $figure->image_url = generateFileUrl($figure->image);
                $figure->avatar_url = generateFileUrl($figure->avatar);
                $result['figure'] = $figure;
            }
        }
        return $result;
    }

    public function exchange(int $user_id, int $item_id): int
    {
        $item = Db::table('virtual_item')
            ->where(['id' => $item_id, 'del_flag' => 0, 'status' => 1])
            ->select(['id', 'name', 'item_type', 'exchange_amount', 'quantity', 'image', 'avatar', 'valid_days', 'create_time'])
            ->first();
        if (!$item) {
            throw new LogicException('虚拟商品不存在');
        }
        if ($item->quantity <= 0) {
            throw new LogicException('库存不足，无法兑换');
        }
        $has = Db::table('virtual_exchange')
            ->where('user_id', $user_id)
            ->where('item_id', $item_id)
            ->where('valid_time', '>', time())
            ->count();
        if ($has) {
            throw new LogicException('只能兑换一次');
        }
        $coin = $this->creditService->getCoin($user_id);
        if ($coin < $item->exchange_amount) {
            throw new LogicException('你的余额不足，无法兑换>');
        }
        Db::beginTransaction();
        try {
            $record_id = Db::table('virtual_exchange')
                ->insertGetId([
                    'user_id' => $user_id,
                    'item_id' => $item_id,
                    'name' => $item->name,
                    'item_type' => $item->item_type,
                    'image' => $item->image,
                    'avatar' => $item->avatar,
                    'exchange_amount' => $item->exchange_amount,
                    'valid_time' => time() + $item->valid_days * 24 * 60 * 60,
                    'create_time' => date('Y-m-d H:i:s'),
                ]);
            if (!$record_id) {
                Db::rollBack();
                throw new LogicException('兑换失败');
            }
            $res = Db::table('virtual_item')
                ->where('id', $item_id)
                ->decrement('quantity', 1);
            if (!$res) {
                Db::rollBack();
                throw new LogicException('兑换失败');
            }
            $res = $this->creditService->setCoin($user_id, -$item->exchange_amount, CoinCate::EXCHANGE->value, $record_id, '兑换' . $item->name);
            if (!$res) {
                Db::rollBack();
                throw new LogicException('兑换失败');
            }
            Db::commit();
            return $record_id;
        } catch (\Throwable $e) {
            Db::rollBack();
            throw new LogicException($e->getMessage());
        }
    }

    public function getExchangeList(array $params): array
    {

        $query = Db::table('virtual_exchange')->where('valid_time', '>', time());
        if (!empty($params['user_id'])) {
            $query->where('user_id', $params['user_id']);
        }
        if (!empty($params['item_type'])) {
            $query->where('item_type', $params['item_type']);
        }
        $page = !empty($params['page']) ? $params['page'] : 1;
        $page_size = !empty($params['page_size']) ? $params['page_size'] : 15;
        $data = $query->select(['id', 'item_id', 'name', 'item_type', 'exchange_amount', 'valid_time', 'image', 'avatar', 'is_active', 'create_time'])
            ->orderBy('create_time', 'desc')
            ->paginate((int)$page_size, page: (int)$page);
        $data = paginateTransformer($data);
        if (!empty($data['items'])) {
            foreach ($data['items'] as $item) {
                $item->image_url = generateFileUrl($item->image);
                $item->avatar_url = generateFileUrl($item->avatar);
                $item->valid_time = date('Y-m-d', $item->valid_time);
            }
        }
        return $data;
    }

    // 穿上
    public function active(int $user_id, int $exchange_id)
    {
        $exchange = Db::table('virtual_exchange')
            ->where('id', $exchange_id)
            ->where('user_id', $user_id)
            ->first();
        if (empty($exchange)) {
            throw new LogicException('兑换记录不存在');
        }
        if ($exchange->valid_time < time()) {
            throw new LogicException('已过期');
        }
        if ($exchange->item_type == VirtualType::FIGURE->value) {
            // 限制只能有一个形象，其他的形象先取消
            Db::table('virtual_exchange')
                ->where('user_id', $user_id)
                ->where('item_type', VirtualType::FIGURE->value)
                ->update(['is_active' => 0]);
            Db::table('user')
                ->where('id', $user_id)
                ->update(['avatar_icon' => $exchange['avatar']]);
        } elseif ($exchange->item_type == VirtualType::MEDAL->value) {
            // 限制只能激活4个勋章
            $count = Db::table('virtual_exchange')
                ->where('user_id', $user_id)
                ->where('item_type', VirtualType::MEDAL->value)
                ->where('valid_time', '>', time())
                ->where('is_active', 1)
                ->count();
            if ($count >= 4) {
                throw new LogicException('最多只能同时穿戴4个勋章');
            }
        }
        $res = Db::table('virtual_exchange')
            ->where('id', $exchange_id)
            ->update(['is_active' => 1, 'active_time' => (new DateTime())->format('Y-m-d H:i:s.u')]);
        if (!$res) {
            throw new LogicException('激活失败');
        }
        return true;
    }

    // 卸下
    public function cancel(int $user_id, int $exchange_id)
    {
        $res = Db::table('virtual_exchange')
            ->where('id', $exchange_id)
            ->where('user_id', $user_id)
            ->update(['is_active' => 0]);
        if (!$res) {
            throw new LogicException('取消失败');
        }
        return true;
    }

    public function avatarSetting(int $user_id, array $params)
    {
        $update = [
            'show_icon' => $params['show_icon'] ?? 0,
            'show_medal' => $params['show_medal'] ?? 0,
        ];
        Db::table('user')
            ->where('id', $user_id)
            ->update($update);
        return true;
    }

    #[Cacheable(prefix: 'default_avatar_icon', ttl: 3600)]
    public function getDefaultAvatarIcon()
    {
        $avatar = Db::table('virtual_item')
            ->where('is_default', '=', 1)
            ->where('del_flag', '=', 0)
            ->value('avatar');
        return generateFileUrl($avatar);
    }
}