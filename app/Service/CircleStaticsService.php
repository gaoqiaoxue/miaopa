<?php

namespace App\Service;

use Carbon\Carbon;
use Hyperf\DbConnection\Db;

class CircleStaticsService
{
    // 每个游客每天记录一次
    public function incrementCoreClick(int $circle_id, int $core_id): bool
    {
        $redisClient = redisHandler();
        $date = date('Y-m-d');
        $userClickKey = "circle:coreclick:{$date}:{$circle_id}:{$core_id}";
        $isNewClick = $redisClient->setnx($userClickKey, 1);
        if ($isNewClick) {
            $redisClient->expireat($userClickKey, strtotime('tomorrow midnight'));

            $hashKey = "circle:clicks:{$date}";
            $redisClient->hIncrBy($hashKey, (string)$circle_id, 1);
        }
        return true;
    }

    // 每个用户每天记录一次
    public function incrementClick(int $circle_id, int $user_id): bool
    {
        $redisClient = redisHandler();
        $date = date('Y-m-d');
        $userClickKey = "circle:userclick:{$date}:{$circle_id}:{$user_id}";
        $isNewClick = $redisClient->setnx($userClickKey, 1);
        if ($isNewClick) {
            $redisClient->expireat($userClickKey, strtotime('tomorrow midnight'));

            $hashKey = "circle:clicks:{$date}";
            $redisClient->hIncrBy($hashKey, (string)$circle_id, 1);
        }
        return true;
    }

    /**
     * 获取当日排名及趋势
     */
    public function getDailyRankingWithTrend(int $limit = 10): array
    {
        $today = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));

        // 获取今日排名数据
        $todayRanking = $this->getRawDailyRanking($today, [], $limit);
        $circle_ids = array_column($todayRanking, 'circle_id');

        // 获取昨日排名数据
        $yesterdayRanking = $this->getRawDailyRanking($yesterday, $circle_ids);
        $yesterdayMap = array_column($yesterdayRanking, 'click_count', 'circle_id');

        return $this->getRankingData($todayRanking, $yesterdayMap, $circle_ids);
    }

    /**
     * 获取周期排名及趋势 (7日/15日)
     */
    public function getPeriodRankingWithTrend(int $days, int $limit = 10): array
    {
        $endDate = Carbon::yesterday()->toDateString();
        $startDate = Carbon::now()->subDays($days)->toDateString();

        $compareEndDate = Carbon::parse($startDate)->subDay()->toDateString();
        $compareStartDate = Carbon::parse($startDate)->subDays($days)->toDateString();

        // 获取当前周期排名
        $currentRanking = $this->getRawPeriodRanking($startDate, $endDate, [], $limit);
        $circle_ids = array_column($currentRanking, 'circle_id');

        // 获取对比周期排名
        $compareRanking = $this->getRawPeriodRanking($compareStartDate, $compareEndDate, $circle_ids);
        $compareMap = array_column($compareRanking, 'click_count', 'circle_id');

        return $this->getRankingData($currentRanking, $compareMap, $circle_ids);
    }

    /**
     * 获取原始每日排名数据
     */
    private function getRawDailyRanking(string $date, array $circle_ids = [], int $limit = 0): array
    {
        if ($date === date('Y-m-d')) {
            $redisClient = redisHandler();
            // 今日数据从Redis获取
            $hashKey = "circle:clicks:{$date}";
            // 使用HGETALL获取所有数据（比KEYS+GET高效）
            $allData = $redisClient->hGetAll($hashKey);
            $result = [];
            foreach ($allData as $circleId => $clickCount) {
                $item = new \stdClass();
                $item->circle_id = (int)$circleId;
                $item->click_count = (int)$clickCount;
                $result[] = $item;
//                $result[] = [
//                    'circle_id' => (int)$circleId,
//                    'click_count' => (int)$clickCount
//                ];
            }
        } else {
            // 历史数据从数据库获取
            $query = Db::table('circle_view_statics')
                ->where('sta_date', $date);
            if (!empty($circle_ids)) {
                $query->whereIn('circle_id', $circle_ids);
            }
            $result = $query->select('circle_id', 'click_count')
                ->orderBy('click_count', 'desc')
                ->get()
                ->toArray();
        }

        // 按点击量降序排序
        usort($result, function ($a, $b) {
            return $b->click_count <=> $a->click_count;
        });

        return !empty($limit) ? array_slice($result, 0, $limit) : $result;
    }

    /**
     * 获取原始周期排名数据
     */
    private function getRawPeriodRanking(string $startDate, string $endDate, array $circle_ids = [], int $limit = 0): array
    {
        $query = Db::table('circle_view_statics')
            ->whereBetween('sta_date', [$startDate, $endDate]);
        if (!empty($circle_ids)) {
            $query->whereIn('circle_id', $circle_ids);
        }
        if (!empty($limit)) {
            $query->limit($limit);
        }
        return $query->select(['circle_id', Db::raw('SUM(click_count) as click_count')])
            ->groupBy('circle_id')
            ->orderBy('click_count', 'desc')
            ->get()
            ->toArray();
    }

    private function getRankingData($currentRanking, $compareMap, $circle_ids)
    {
        $circles = Db::table('circle')
            ->whereIn('id', $circle_ids)
            ->pluck('name', 'id')
            ->toArray();
        // 计算趋势
        $result = [];
        foreach ($currentRanking as $index => $item) {
            $previousCount = $compareMap[$item->circle_id] ?? null;
            $result[] = [
                'rank' => $index + 1,
                'circle_id' => $item->circle_id,
                'name' => $circles[$item->circle_id] ?? '未知',
                'count' => $item->click_count,
                'compare_status' => $this->getTrend($item->click_count, $previousCount)
            ];
        }
        return $result;
    }

    /**
     * 获取比较趋势 (上升/下降/持平)
     */
    private function getTrend(int $currentCount, ?int $previousCount): int
    {
        if ($previousCount === null) {
            return 1; // 新上榜
        }
        if ($currentCount < $previousCount) {
            return 0;
        } else {
            return 1;
        }
    }

    public function persistDailyStats(): void
    {
        $redisClient = redisHandler();
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $hashKey = "circle:clicks:{$yesterday}";
        // 获取Hash中的所有字段和值
        $allData = $redisClient->hGetAll($hashKey);
        if (empty($allData)) {
            return; // 没有数据需要处理
        }
        // 批量插入数据库
        $batchData = [];
        foreach ($allData as $circleId => $clickCount) {
            $batchData[] = [
                'sta_date' => $yesterday,
                'circle_id' => (int)$circleId,
                'click_count' => (int)$clickCount,
                'create_time' => date('Y-m-d H:i:s')
            ];
        }
        // 使用批量插入优化性能
        Db::table('circle_view_statics')->insert($batchData);
        // 删除Redis中的Hash键
        $redisClient->del($hashKey);
    }
}