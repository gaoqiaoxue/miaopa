<?php

namespace App\Service;

use App\Constants\AbleStatus;
use App\Constants\ActiveStatus;
use App\Constants\ActivityType;
use App\Constants\ActivityUserStatus;
use App\Exception\ParametersException;
use Carbon\Carbon;
use http\Params;
use Hyperf\Cache\Annotation\Cacheable;
use Hyperf\Collection\Arr;
use Hyperf\DbConnection\Db;
use Hyperf\Di\Annotation\Inject;

class ActivityService
{
    #[Inject]
    protected FileService $fileService;

    public function __construct()
    {
        $this->checkStatus();
    }

    #[Cacheable(prefix: 'activity_status', ttl: 60)]
    protected function checkStatus()
    {
        $current = time();
        Db::table('activity')
            ->where('active_status', ActiveStatus::NOT_START)
            ->where('start', '<', $current)
            ->update(['active_status' => ActiveStatus::ONGOING]);
        Db::table('activity')
            ->where('active_status', ActiveStatus::ONGOING)
            ->where('end', '<', $current)
            ->update(['active_status' => ActiveStatus::ENDED]);
        return true;
    }

    public function getList(array $params)
    {
        $query = Db::table('activity');
        if (!empty($params['name'])) {
            $query->where('name', 'like', '%' . $params['name'] . '%');
        }
        if (!empty($params['activity_type'])) {
            $query->where('activity_type', '=', $params['activity_type']);
        }
        if (isset($params['status']) && in_array($params['status'], AbleStatus::getKeys())) {
            $query->where('status', '=', $params['status']);
        }
        if (isset($params['active_status']) && in_array($params['active_status'], ActiveStatus::getKeys())) {
            $query->where('active_status', '=', $params['active_status']);
        }
        if (!empty($params['start_time']) && !empty($params['end_time'])) {
            $query->where('start', '<', strtotime($params['end_time']))
                ->where('end', '>', strtotime($params['start_time']));
        }
        $page = !empty($params['page']) ? $params['page'] : 1;
        $page_size = !empty($params['page_size']) ? $params['page_size'] : 15;
        $data = $query->select(['id', 'cover', 'name', 'activity_type', 'status', 'active_status', 'fee', 'city', 'address', 'start_date', 'end_date', 'create_time'])
            ->orderBy('create_time', 'desc')
            ->paginate((int)$page_size, page: (int)$page);
        $data = paginateTransformer($data);
        if (!empty($data['items'])) {
            $covers = array_column($data['items'], 'cover');
            $covers = $this->fileService->getFilepathByIds($covers);
            foreach ($data['items'] as $item) {
                $item->cover_url = $covers[$item->cover] ?? '';
            }
        }
        return $data;
    }

    public function getInfo(int $activity_id)
    {
        $info = Db::table('activity')
            ->where(['id' => $activity_id])
            ->select(['id', 'bg', 'cover', 'name', 'activity_type', 'organizer', 'is_hot', 'city', 'city_id', 'address', 'fee',
                'start_date', 'end_date', 'start_time', 'end_time', 'weight', 'status', 'active_status', 'tags', 'details', 'create_by', 'create_time'])
            ->first();
        if (empty($info)) {
            throw new ParametersException('活动不存在');
        }
        $info->bg_url = $this->fileService->getFilePathById($info->bg);
        $info->cover_url = $this->fileService->getFilePathById($info->cover);
        $info->tags = json_decode($info->tags, true);
        $info->creater_name = Db::table('sys_user')
            ->where('user_id', '=', $info->create_by)
            ->value('nick_name');
        return $info;
    }

    public function add(array $params): int
    {
        return Db::table('activity')->insertGetId($this->generalData($params, true));
    }

    public function edit(array $params): int
    {
        $activity_id = $params['activity_id'];
        return Db::table('activity')
            ->where(['id' => $activity_id])
            ->update($this->generalData($params));
    }

    protected function generalData(array $data, $is_add = false): array
    {
        $start = strtotime($data['start_date'] . ' ' . $data['end_date']);
        $end = strtotime($data['end_date'] . ' ' . $data['end_time']);
        $result = [
            'bg' => $data['bg'],
            'cover' => $data['cover'],
            'name' => $data['name'],
            'activity_type' => $data['activity_type'],
            'organizer' => $data['organizer'],
            'is_hot' => $data['is_hot'],
            'city' => RegionService::getRegionNameById($data['city_id']),
            'city_id' => $data['city_id'],
            'address' => $data['address'],
            'fee' => $data['fee'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'start' => $start,
            'end' => $end,
            'weight' => $data['weight'],
            'status' => $data['status'],
            'active_status' => $this->getActiveStatus($start, $end),
            'tags' => json_encode(!empty($data['tags']) ? $data['tags'] : []),
            'details' => $data['details'],
            'update_time' => date('Y-m-d H:i:s'),
        ];
        if ($is_add) {
            $result['create_by'] = $data['create_by'] ?: 0;
            $result['create_time'] = date('Y-m-d H:i:s');
        }
        return $result;
    }

    protected function getActiveStatus(int $start, int $end): int
    {
        $cur = time();
        if ($cur < $start) {
            return ActiveStatus::NOT_START->value;
        } elseif ($cur > $end) {
            return ActiveStatus::ENDED->value;
        } else {
            return ActiveStatus::ONGOING->value;
        }
    }

    public function changeStatus(int $activity_id, int $status): int
    {
        return Db::table('activity')
            ->where(['id' => $activity_id])
            ->update(['status' => $status]);
    }

    public function getUsers(array $params): array
    {
        $query = Db::table('activity_user')
            ->leftJoin('user', 'user.id', '=', 'activity_user.user_id')
            ->where('activity_id', $params['activity_id'])
            ->where('activity_user.status', ActivityUserStatus::JOINED);
        if (!empty($params['user_id'])) {
            $query->where('activity_user.user_id', $params['user_id']);
        }
        if (!empty($params['nickname'])) {
            $query->where('user.nickname', 'like', '%' . $params['nickname'] . '%');
        }
        if (!empty($params['sex'])) {
            $query->where('user.sex', '=', $params['sex']);
        }
        if (!empty($params['start_time']) && !empty($params['end_time'])) {
            $query->whereBetween('activity_user.create_time', [$params['start_time'], $params['end_time']]);
        }
        $page = !empty($params['page']) ? $params['page'] : 1;
        $page_size = !empty($params['page_size']) ? $params['page_size'] : 15;
        $data = $query->select(['activity_user.activity_id', 'activity_user.user_id', 'activity_user.create_time', 'user.nickname', 'user.sex', 'user.mobile', 'user.school', 'user.region'])
            ->orderBy('activity_user.create_time', 'desc')
            ->paginate((int)$page_size, page: (int)$page);
        $data = paginateTransformer($data);
        return $data;
    }


    // 获取城市最近有活动的日期
    public function getDates(int $city_id)
    {
        $sql = 'WITH RECURSIVE activity_dates AS (
    SELECT 
        start_date AS activity_date,
        end_date
    FROM mp_activity
    WHERE status = 1 AND active_status in (1,2) AND city_id = :city_id
    
    UNION ALL
    
    SELECT 
        activity_date + INTERVAL 1 DAY,
        end_date
    FROM activity_dates
    WHERE activity_date < end_date
)

SELECT DISTINCT activity_date
FROM activity_dates
WHERE activity_date >= :current_date
ORDER BY activity_date ASC
LIMIT 7;';
        $list = Db::select($sql, ['city_id' => $city_id, 'current_date' => date('Y-m-d')]);
        foreach ($list as $item) {
            $time = strtotime($item->activity_date);
            $item->activity_date = date('m.d', $time);
            $item->week = getChineseWeekday($time);
        }
        return $list;
    }

    // 获取城市热门活动
    public function getHot(int $city_id): array
    {
        $items = Db::table('activity')
            ->where('status', AbleStatus::ENABLE->value)
            ->where('city_id', $city_id)
            ->whereIn('active_status', [ActiveStatus::NOT_START->value, ActiveStatus::ONGOING->value])
            ->select(['id', 'cover', 'name', 'activity_type', 'active_status', 'fee', 'city', 'address', 'start_date', 'end_date', 'start_time', 'end_time', 'tags', 'create_time'])
            ->orderBy('is_hot', 'desc')
            ->orderBy('weight', 'desc')
            ->orderBy('create_time', 'desc')
            ->limit(3)
            ->get()
            ->toArray();
        var_dump(Arr::last(Db::getQueryLog()));
        return $this->transformerList($items);
    }

    public function getApiList(array $params)
    {
        $query = Db::table('activity')
            ->where('status', AbleStatus::ENABLE->value)
            ->whereIn('active_status', [ActiveStatus::NOT_START->value, ActiveStatus::ONGOING->value]);
        if (!empty($params['keyword'])) {
            $query->where('name', 'like', '%' . $params['keyword'] . '%');
        }
        if (!empty($params['city_id'])) {
            $query->where('city_id', $params['city_id']);
        }
        if (!empty($params['date'])) {
            $start = Carbon::parse($params['date'])->startOfDay()->timestamp;
            $end = $start + 86400;
            $query->where('start', '<', $start)
                ->where('end', '>', $end);
        }
        $page = !empty($params['page']) ? $params['page'] : 1;
        $page_size = !empty($params['page_size']) ? $params['page_size'] : 15;
        $data = $query->select(['id', 'cover', 'name', 'activity_type', 'active_status', 'fee', 'city', 'address', 'start_date', 'end_date', 'start_time', 'end_time', 'tags', 'create_time'])
            ->orderBy('weight', 'desc')
            ->orderBy('create_time', 'desc')
            ->paginate((int)$page_size, page: (int)$page);
        $data = paginateTransformer($data);
        $data['items'] = $this->transformerList($data);
        return $data;
    }

    protected function transformerList(array $items): array
    {
        if (!empty($items)) {
            $covers = array_column($items, 'cover');
            $covers = $this->fileService->getFilepathByIds($covers);
            foreach ($items as $item) {
                $item->tags = json_decode($item->tags, true);
                $item->cover_url = $covers[$item->cover] ?? '';
                $item->activity_type_text = ActivityType::from($item->activity_type)->getMessage();
            }
        }
        return $items;
    }

}
