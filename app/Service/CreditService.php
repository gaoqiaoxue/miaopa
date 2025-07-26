<?php

namespace App\Service;

use App\Constants\CoinCate;
use App\Constants\PrestigeCate;
use App\Exception\LogicException;
use Carbon\Carbon;
use Hyperf\Cache\Annotation\Cacheable;
use Hyperf\Cache\Annotation\CacheEvict;
use Hyperf\DbConnection\Db;
use Hyperf\Di\Annotation\Inject;
use PhpParser\Node\Stmt\Case_;

class CreditService
{
    #[Inject]
    protected ConfigService $configService;

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

    #[CacheEvict(prefix: 'user_credit', value: "#{user_id}")]
    public function setCoin(int $user_id, int $num, string $cate, int $refer_id = 0, string $remark = '', int $refer_type = 0, int $refer_uid = 0): bool
    {
        return $this->setCreditLog('coin', $user_id, $num, $cate, $refer_id, $remark, $refer_type, $refer_uid);
    }

    #[CacheEvict(prefix: 'user_credit', value: "#{user_id}")]
    public function setPrestige(int $user_id, int $num, string $cate, int $refer_id = 0, string $remark = '', int $refer_type = 0, int $refer_uid = 0): bool
    {
        return $this->setCreditLog('prestige', $user_id, $num, $cate, $refer_id, $remark, $refer_type, $refer_uid);
    }

    protected function setCreditLog(string $field, int $user_id, int $num, string $cate, int $refer_id = 0, string $remark = '', int $refer_type = 0, int $refer_uid = 0): bool
    {
        if ($num == 0) {
            return true;
        }
        Db::beginTransaction();
        try {
            if ($num > 0) {
                $res = Db::table('user_credit')->where('user_id', $user_id)->increment($field, $num);
            } else {
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
                'refer_type' => $refer_type,
                'refer_id' => $refer_id,
                'refer_uid' => $refer_uid,
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
        return $this->getLogs('coin', $params);
    }

    public function getPrestigeLogs(array $params): array
    {
        return $this->getLogs('prestige', $params);
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
    public function getCoinTask(int $user_id, string $date = ''): array|false
    {
        if (empty($date)) {
            $date = date('Y-m-d');
        }
        $redis = redisHandler();
        return $redis->hGetAll('coin:' . $date . ':' . $user_id);
    }

    // 评论 发帖 报名获取金币，每天一次
    public function finishCoinTask(int $user_id, CoinCate $cate, int $refer_id = 0, string $remark = '', string $date = ''): bool
    {
        if (empty($date)) {
            $date = date('Y-m-d');
        }
        $type = match ($cate) {
            CoinCate::COMMENT => 'comment',
            CoinCate::POST => 'post',
            CoinCate::ACTIVITY => 'activity',
            default => false
        };
        if (empty($type)) {
            return false;
        }
        $redis = redisHandler();
        $result = $redis->hGet('coin:' . $date . ':' . $user_id, $type);
        if (!empty($result)) {
            return true;
        }
        $key = $type . "_coins";
        $coins = $this->configService->getValue($key);
        if (!empty($coins)) {
            $this->setCoin($user_id, $coins, $cate->value, $refer_id, $remark);
        }
        $redis->hSet('coin:' . $date . ':' . $user_id, $type, 1);
        $time = Carbon::now()->endOfDay()->timestamp - time();
        $redis->expire('prestige:' . $date . ':' . $user_id, $time);
        return true;
    }

    // 用户停留时长获取金币
    public function finishStayTask(int $user_id, int $minute): int
    {
        if (empty($date)) {
            $date = date('Y-m-d');
        }
        $redis = redisHandler();
        $has = $redis->hGet('coin:' . $date . ':' . $user_id, 'stay');
        $has = empty($has) ? 0 : $has;
        if (!empty($has) && $has >= $minute) {
            return 0;
        }
        $setting = $this->configService->getValue('stay_time_config');
        $coin = 0;
        $time = 0;
        foreach ($setting as $item) {
            if ($item['time'] <= $minute && $item['time'] > $has) {
                $coin = $item['coins'];
                $time = $item['time'];
                break;
            }
        }
        if (empty($coin)) {
            return 0;
        }
        $this->setCoin($user_id, $coin, CoinCate::STAY->value, 0, '停留时间超过' . $minute . '分钟');
        $redis->hSet('coin:' . $date . ':' . $user_id, 'stay', $time);
        $time = Carbon::now()->endOfDay()->timestamp - time();
        $redis->expire('coin:' . $date . ':' . $user_id, $time);
        return $coin;
    }

    // 声望获取配置
    public function getPrestigeSetting()
    {
        return [
            'dynamic' => ['name' => '发动态', 'times' => 3, 'prestige' => 3], // 发动态，每天最多3次，每次3个声望
            'qa' => ['name' => '发问答', 'times' => 3, 'prestige' => 3],// 发问答
            'be_commented' => ['name' => '被回复', 'times' => 3, 'prestige' => 3], // 被回复
            'be_liked' => ['name' => '获赞', 'times' => 3, 'prestige' => 3],// 获赞
            'report' => ['name' => '举报成功', 'times' => 3, 'prestige' => 3],//举报成功
            'fans' => ['name' => '粉丝', 'times' => 3, 'prestige' => 3],//获取粉丝
            'like' => ['name' => '点赞', 'times' => 3, 'prestige' => 3],// 点赞
            'share' => ['name' => '分享', 'times' => 3, 'prestige' => 3],// 分享
            'comment' => ['name' => '回复', 'times' => 3, 'prestige' => 3],// 回复
            'follow' => ['name' => '关注', 'times' => 3, 'prestige' => 3],// 关注
        ];
    }

    // 声望等级设置
    public function prestigeLevelSetting()
    {
        return [
            ['level' => 1, 'name' => '契约萌新', 'prestige' => 0],
            ['level' => 2, 'name' => '弹幕游侠', 'prestige' => 10],
            ['level' => 3, 'name' => '引航人', 'prestige' => 50],
            ['level' => 4, 'name' => '应援大师', 'prestige' => 200],
            ['level' => 5, 'name' => '次元领主', 'prestige' => 800],
            ['level' => 6, 'name' => '真理宗师', 'prestige' => 2000],
            ['level' => 7, 'name' => '人类之光', 'prestige' => 5000],
            ['level' => 8, 'name' => '人未知', 'prestige' => 10000],
        ];
    }

    public function getPrestigeLevelName(int $prestige): string
    {
        $level = '';
        $level_setting = $this->prestigeLevelSetting();
        foreach ($level_setting as $item) {
            if ($prestige > $item['prestige']) {
                $level = $item['name'];
            } else {
                break;
            }
        }
        return $level;
    }

    public function getPrestigeTask(int $user_id)
    {
        $setting = $this->getPrestigeSetting();
        $date = date('Y-m-d');
        $redis = redisHandler();
        $user_times = $redis->hGetAll('prestige:' . $date . ':' . $user_id);
        foreach ($setting as $key => $item) {
            $setting[$key]['done_times'] = $user_times[$key] ?? 0;
        }
        return $setting;
    }

    public function finishPrestigeTask(int $user_id, PrestigeCate $cate, int $refer_id = 0, string $remark = '', int $refer_type = 0, int $refer_uid = 0, string $date = ''): bool
    {
        $date = $date ?: date('Y-m-d');
        $redis = redisHandler();
        $type = strtolower($cate->name);
        $setting = $this->getPrestigeSetting()[$type] ?? [];
        $times = $setting['times'] ?? 0;
        $prestige = $setting['prestige'] ?? 0;
        if ($prestige == 0 || $times == 0) {
            return true;
        }
        $user_times = $redis->hGet('prestige:' . $date . ':' . $user_id, $type);
        if ($user_times >= $times) {
            return true;
        }
        $this->setPrestige($user_id, $prestige, $cate->value, $refer_id, $remark, $refer_type, $refer_uid);
        $redis->hIncrBy('prestige:' . $date . ':' . $user_id, $type, 1);
        $time = Carbon::now()->endOfDay()->timestamp - time();
        $redis->expire('prestige:' . $date . ':' . $user_id, $time);
        return true;
    }

}