<?php

namespace App\Service;

use Carbon\Carbon;
use Hyperf\DbConnection\Db;

class UserStaticsService
{
    /**
     * 记录活跃用户/访客
     * @param string $type user/guest
     * @param int $user_id
     */
    public function recordActive(string $type, int $user_id): void
    {
        $redisClient = redisHandler();
        $date = date('Y-m-d');
        $hour = date('H');

        // 当天按小时统计
        $hourKey = "active:{$type}:{$date}:{$hour}";
        $redisClient->sAdd($hourKey, $user_id);
        $redisClient->expire($hourKey, 86400 * 2); // 保留2天

        // 当天汇总统计
        $summaryKey = "active:{$type}:summary:{$date}";
        $redisClient->sAdd($summaryKey, $user_id);
        $redisClient->expire($summaryKey, 86400 * 2); // 保留2天
    }

    /**
     * 获取当天按小时统计
     */
    public function getTodayHourlyStats(): array
    {
        $redisClient = redisHandler();
        $date = date('Y-m-d');
        $result = [];
        for ($hour = 0; $hour < 24; $hour++) {
            $user_key = "active:user:{$date}:{$hour}";
            $core_key = "active:guest:{$date}:{$hour}";
            $result[] = [
                'time' => str_pad($hour, 2, '0', STR_PAD_LEFT) . ':00',
                'guest' => $redisClient->sCard($core_key),
                'user' => $redisClient->sCard($user_key),
            ];
        }

        return $result;
    }

    /**
     * 获取周期排名及趋势 (7日/15日)
     */
    public function getMultiDaySummaryStats(int $days): array
    {
        $endDate = Carbon::yesterday()->toDateString();
        $startDate = Carbon::now()->subDays($days)->toDateString();
        $data = Db::table('user_statics')
            ->whereBetween('sta_date', [$startDate, $endDate])
            ->select(['sta_date', 'guest_count', 'user_count'])
            ->orderBy('sta_date', 'asc')
            ->get()
            ->toArray();
        $data = array_column($data, null, 'sta_date');
        $result = [];
        for ($i = $days; $i >= 1; $i--) {
            $sta_date = Carbon::now()->subDays($i)->toDateString();
            $result[] = [
                'time' => $sta_date,
                'guest' => $data[$sta_date]['guest_count'] ?? 0,
                'user' => $data[$sta_date]['user_count'] ?? 0,
            ];
        }
        return $result;
    }

    /**
     * 将前一天的统计数据持久化到数据库
     */
    public function persistYesterdayStats(): void
    {
        $yesterday = Carbon::yesterday()->toDateString();
        $redisClient = redisHandler();
        $raw_data = [];
        for ($hour = 0; $hour < 24; $hour++) {
            $raw_data[] = [
                'time' => str_pad($hour, 2, '0', STR_PAD_LEFT) . ':00',
                'guest' => $redisClient->sCard("active:guest:{$yesterday}:{$hour}"),
                'user' => $redisClient->sCard("active:user:{$yesterday}:{$hour}"),
            ];
        }
        Db::table('user_statics')->insert([
            'sta_date' => $yesterday,
            'guest_count' => $redisClient->sCard("active:guest:summary:{$yesterday}"),
            'user_count' => $redisClient->sCard("active:user:summary:{$yesterday}"),
            'hour_raw_data' => json_encode($raw_data),
            'create_time' => date('Y-m-d H:i:s')
        ]);
    }

    // 获取今天昨天新增的游客数和注册用户数
    public function getUserStatics()
    {
        $today = Carbon::today()->startOfDay()->format('Y-m-d');
        $yesterday = Carbon::yesterday()->startOfDay()->format('Y-m-d');
        $start_time = Carbon::yesterday()->startOfDay()->format('Y-m-d H:i:s');

        $core_statics = Db::table('user_core')
            ->where('create_time', '>', $start_time)
            ->selectRaw('DATE(create_time) as date, COUNT(*) as count')
            ->groupBy('date')
            ->get()
            ->toArray();
        $core_statics = array_column($core_statics, 'count', 'date');
        $core_today = $core_statics[$today] ?? 0;
        $core_yesterday = $core_statics[$yesterday] ?? 0;

        $user_statics = Db::table('user')
            ->where('create_time', '>', $start_time)
            ->selectRaw('DATE(create_time) as date, COUNT(*) as count')
            ->groupBy('date')
            ->get()
            ->toArray();
        $user_statics = array_column($user_statics, 'count', 'date');
        $user_today = $user_statics[$today] ?? 0;
        $user_yesterday = $user_statics[$yesterday] ?? 0;

        $register_rate_today = empty($core_today) ? 0 : round($user_today / $core_today * 100, 2);
        $register_rate_yesterday = empty($core_yesterday) ? 0 : round($user_yesterday / $core_yesterday * 100, 2);
        return [
            'guest' => [
                'today' => $core_today,
                'compare_yesterday' => abs($core_today - $core_yesterday),
                'compare_status' => $core_today >= $core_yesterday ? 1 : 0,
            ],
            'user' => [
                'today' => $user_today,
                'compare_yesterday' => abs($user_today - $user_yesterday),
                'compare_status' => $user_today >= $user_yesterday ? 1 : 0,
            ],
            'register_rate' => [
                'today' => $register_rate_today,
                'compare_yesterday' => abs(round($register_rate_today - $register_rate_yesterday,2)),
                'compare_status' => $register_rate_today >= $register_rate_yesterday ? 1 : 0,
            ],
        ];
    }

}