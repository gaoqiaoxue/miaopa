<?php

namespace App\Service;

use App\Constants\CoinCate;
use App\Exception\LogicException;
use Carbon\Carbon;
use Hyperf\DbConnection\Db;
use Hyperf\Di\Annotation\Inject;

class UserSignService
{
    #[Inject]
    protected CreditService $creditService;

    #[Inject]
    protected ConfigService $configService;

    public function getInfo(int $user_id): array
    {
        $coin = $this->creditService->getCoin($user_id);
        $sign = $this->getUserSignStatus($user_id);

        $config = $this->configService->getConfig();
        $daily_coins = $config->daily_sign_coins;
        $continuous_sign = $config->continuous_sign_config;

        $sign_list = [];
        $days = $sign['is_signed'] ? $sign['continuous_days'] : $sign['continuous_days'] + 1; // 今天是签到的第几天

        $records = [];
        $yesterday = Carbon::yesterday()->startOfDay()->toDateString();
        if ($sign['continuous_days'] > 0) {
            $records = Db::table('user_sign_record')
                ->where('user_id', $user_id)
                ->where('sign_time', '>=', $yesterday)
                ->select(['date', Db::raw('coin + continuous_coin as sum')])
                ->get()
                ->toArray();
            $records = array_column($records, 'sum', 'date');
        }
        if ($days >= 2) { // 昨天已经签到
            $yesterday_coin = $records[$yesterday] ?? 0;
            $sign_list[] = ['day' => '已签', 'is_signed' => 1, 'coins' => $yesterday_coin];
        }
        if ($sign['is_signed']) {
            $today_date = date('Y-m-d');
            $today_coin = $records[$today_date] ?? 0;
            $sign_list[] = ['day' => '已签', 'is_signed' => 1, 'coins' => $today_coin];
        } else {
            $sign_list[] = ['day' => '今天', 'is_signed' => 0, 'coins' => $daily_coins];
        }
        $continuous_coin_set = array_column($continuous_sign, 'coins', 'time');
        $sign_list[] = ['day' => '明天', 'is_signed' => 0, 'coins' => $daily_coins + ($continuous_coin_set[$days + 1] ?? 0)];
        $has = count($sign_list);
        for ($i = 2; $i <= 8 - $has; $i++) {
            $sign_list[] = ['day' => '第' . $days + $i . '天', 'is_signed' => 0, 'coins' => $daily_coins + ($continuous_coin_set[$days + $i] ?? 0)];
        }
        $continuous_task = [];
        foreach ($continuous_sign as &$item) {
            $item['is_finished'] = $sign['is_signed'];
            $item['continuous_days'] = $sign['continuous_days'];
            $continuous_task = $item;
            $finished = $sign['continuous_days'] >= $item['time'] ? 1 : 0;
            if($finished == 0){
                break;
            }
        }
        $coin_finish_status = $this->creditService->getCoinTask($user_id);
        $stay_time_config = $config->stay_time_config;
        $stay_time = $coin_finish_status['stay'] ?? 0;
        $stay_task = [];
        foreach ($stay_time_config as &$item){
            $item['stay_time'] = $stay_time;
            $item['is_finished'] = $stay_time >= $item['time'] ? 1 : 0;
            $stay_task = $item;
            if($item['is_finished'] == 0){
                break;
            }
        }
        return [
            'coin' => $coin,
            'is_signed' => $sign['is_signed'],
            'continuous_days' => $sign['continuous_days'],
            'daily_coins' => $daily_coins,
            'sign_list' => $sign_list,
            'task' => [
//                'continuous_sign' => $continuous_sign,
                'continuous' => $continuous_task,
                'post' => [
                    'coins' => $config->post_coins,
                    'is_finished' => $coin_finish_status['post'] ?? 0,
                ],
                'comment' => [
                    'coins' => $config->comment_coins,
                    'is_finished' => $coin_finish_status['comment'] ?? 0,
                ],
                'activity' => [
                    'coins' => $config->activity_coins,
                    'is_finished' => $coin_finish_status['activity'] ?? 0,
                ],
//                'stay_time' => $stay_time_config,
                'stay' => $stay_task
            ],
        ];
    }

    // 获取用户当天的签到状态，以及连续签到天数
    public function getUserSignStatus(int $user_id): array
    {
        $record = Db::table('user_sign')
            ->where('user_id', $user_id)
            ->first();
        $today = Carbon::now()->startOfDay()->timestamp;
        $yesterday = $today - 86400;
        if (!empty($record) && $record->last_sign_time > $yesterday) {
            $is_signed = $record->last_sign_time > $today ? 1 : 0;
            $continuous_days = $record->continuous_days;
        } else {
            $is_signed = 0;
            $continuous_days = 0;
        }
        return [
            'record' => $record,
            'is_signed' => $is_signed,
            'continuous_days' => $continuous_days,
        ];
    }

    public function sign(int $user_id): bool
    {
        $sign_status = $this->getUserSignStatus($user_id);
        if ($sign_status['is_signed']) {
            throw new LogicException('已签到，明日再来');
        }
        $current_time = time();
        $current_date = date('Y-m-d H:i:s');
        $continuous_days = $sign_status['continuous_days'];
        $config = $this->configService->getConfig();
        $daily_coins = $config->daily_sign_coins;
        $continuous_sign = $config->continuous_sign_config;
        $continuous_days = $continuous_days + 1;
        Db::beginTransaction();
        try {

            if (empty($sign_status['record'])) {
                $res = Db::table('user_sign')->insert([
                    'user_id' => $user_id,
                    'last_sign_time' => $current_time,
                    'continuous_days' => $continuous_days,
                    'create_time' => $current_date,
                    'update_time' => $current_date,
                ]);
            } else {
                $res = Db::table('user_sign')->where('user_id', $user_id)->update([
                    'last_sign_time' => $current_time,
                    'continuous_days' => $continuous_days,
                    'update_time' => $current_date,
                ]);
            }
            if (!$res) {
                Db::rollBack();
                throw new LogicException('更新失败');
            }
            $res = $this->creditService->setCoin($user_id, $daily_coins, CoinCate::SIGN->value, 0, '签到奖励');
            if (!$res) {
                Db::rollBack();
                throw new LogicException('签到奖励获取失败');
            }
            $continuous_coin = 0;
            foreach ($continuous_sign as $item) {
                if ($item['time'] == $continuous_days) {
                    $continuous_coin = $item['coins'];
                    break;
                } elseif ($item['time'] > $continuous_days) {
                    break;
                }
            }
            if ($continuous_coin > 0) {
                $res = $this->creditService->setCoin($user_id, $daily_coins, CoinCate::CON_SIGN->value, 0, '连续签到奖励');
                if (!$res) {
                    Db::rollBack();
                    throw new LogicException('签到奖励获取失败');
                }
            }
            $res = Db::table('user_sign_record')->insert([
                'user_id' => $user_id,
                'date' => date('Y-m-d', $current_time),
                'sign_time' => $current_date,
                'continuous_days' => $continuous_days,
                'coin' => $daily_coins,
                'continuous_coin' => $continuous_coin
            ]);
            if (!$res) {
                Db::rollBack();
                throw new LogicException('签到记录失败');
            }
            Db::commit();
            return true;
        } catch (\Throwable $ex) {
            Db::rollBack();
            throw new LogicException($ex->getMessage());
        }
    }


}