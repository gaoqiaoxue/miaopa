<?php

namespace App\Service;

use App\Exception\LogicException;
use Hyperf\Cache\Annotation\Cacheable;
use Hyperf\Cache\Annotation\CacheEvict;
use Hyperf\Context\ApplicationContext;
use Hyperf\DbConnection\Db;
use Hyperf\Di\Annotation\Inject;

class CreditService
{
    #[Cacheable(prefix: "user_credit", ttl: 3600)]
    public function getUserCredit(int $user_id)
    {
        if ($user_id <= 0) {
            return [
                'coin' => 0,
                'prestige' => 0
            ];
        }
        $info = Db::table('user_credit')
            ->where('user_id', $user_id)
            ->select(['coin', 'prestige'])
            ->first();
        if (empty($info)) {
            Db::table('user_credit')->insert([
                'user_id' => $user_id,
                'coin' => 0,
                'prestige' => 0
            ]);
            return [
                'coin' => 0,
                'prestige' => 0
            ];
        } else {
            return [
                'coin' => $info->coin,
                'prestige' => $info->prestige
            ];
        }
    }

    #[Inject]
    protected ConfigService $configService;

    public function getCoin(int $user_id)
    {
        $info = $this->getUserCredit($user_id);
        return $info['coin'] ?? 0;
    }

    public function getPrestige(int $user_id)
    {
        $info = $this->getUserCredit($user_id);
        return $info['prestige'] ?? 0;
    }

    #[CacheEvict(prefix: 'user_credit', value: "_#{user_id}")]
    public function setCoin(int $user_id, int $num, string $cate, int $refer_id = 0, string $remark = ''): bool
    {
        return $this->setCreditLog('coin', $user_id, $num, $cate, $refer_id, $remark);
    }

    #[CacheEvict(prefix: 'user_credit', value: "_#{user_id}")]
    public function setPrestige(int $user_id, int $num, string $cate, int $refer_id = 0, string $remark = ''): bool
    {
        return $this->setCreditLog('prestige', $user_id, $num, $cate, $refer_id, $remark);
    }

    protected function setCreditLog(string $field, int $user_id, int $num, string $cate, int $refer_id = 0, string $remark = ''): bool
    {
        if($num == 0){
            return true;
        }
        Db::beginTransaction();
        try {
            if($num > 0){
                $res = Db::table('user_credit')->where('user_id', $user_id)->increment($field, $num);
            }else{
                $abs = abs($num);
                $res = Db::table('user_credit')->where('user_id', $user_id)->where($field, '>=', $abs)->decrement($field, $abs);
            }
            if (!$res) {
                Db::rollBack();
                throw new LogicException('设置失败');
            }
            $table = 'user_' . $field . '_log';
            $res = Db::table($table)->insert([
                'user_id' => $user_id,
                'type' => $num > 0 ? 1 : 2,
                'num' => $num,
                'cate' => $cate,
                'refer_id' => $refer_id,
                'remark' => $remark,
                'create_time' => date('Y-m-d H:i:s'),
            ]);
            if (!$res) {
                Db::rollBack();
                throw new LogicException('设置失败');
            }
            Db::commit();
            return true;
        } catch (\Throwable $ex) {
            Db::rollBack();
            throw new LogicException($ex->getMessage());
        }
    }

    public function getCoinLogs(array $params): array
    {
        return $this->getLogs('coin',$params);
    }

    public function getPrestigeLogs(array $params): array
    {
        return $this->getLogs('prestige',$params);
    }

    public function getLogs(string $field, array $params = []): array
    {
        $table = 'user_' . $field . '_log';
        $query = Db::table($table);
        if (!empty($params['user_id'])) {
            $query->where('user_id', $params['user_id']);
        }
        if (!empty($params['cate'])) {
            $query->where('cate', $params['cate']);
        }
        if (!empty($params['refer_id'])) {
            $query->where('refer_id', $params['refer_id']);
        }
        $page = isset($params['page']) ? $params['page'] : 1;
        $page_size = isset($params['page_size']) ? $params['page_size'] : 15;
        $list = $query->select(['id', 'user_id', 'type', 'num', 'cate', 'refer_id', 'remark', 'create_time'])
            ->orderBy('create_time', 'desc')
            ->paginate((int)$page_size, page: (int)$page);
        $list = paginateTransformer($list);
        return $list;
    }

    // 获取当天评论 发帖 报名获取金币的状态
    public function getCoinTaskStatus(int $user_id, string $type, string $date = ''): bool
    {
        if (empty($date)) {
            $date = date('Y-m-d');
        }
        $container = ApplicationContext::getContainer();
        $redis = $container->get(\Hyperf\Redis\Redis::class);
        $result = $redis->get('coin:' . $date . ':' . $user_id . ':' . $type);
        return $result ? true : false;
    }

    // 评论 发帖 报名获取金币，每天一次
    public function finishCoinTaskStatus(int $user_id, string $type, string $date = '', string $cate = '', int $refer_id = 0): bool
    {
        if (empty($date)) {
            $date = date('Y-m-d');
        }
        $container = ApplicationContext::getContainer();
        $redis = $container->get(\Hyperf\Redis\Redis::class);
        $result = $redis->get('coin:' . $date . ':' . $user_id . ':' . $type);
        if (!empty($result)) {
            return true;
        }
        $key = $type . "_coins";
        $coins = $this->configService->getValue($key);
        if (!empty($coins)) {
            $this->setCoin($user_id, $coins, $cate, $refer_id);
        }
        $redis->set('coin:' . $date . ':' . $user_id . ':' . $type, 1, ['EX' => 86400]);
        return true;
    }
}