<?php

namespace App\Service;

use App\Constants\AbleStatus;
use App\Constants\ActiveStatus;
use App\Constants\ActivityType;
use App\Constants\ActivityUserStatus;
use App\Exception\LogicException;
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

    #[Inject]
    protected UserViewRecordService $viewService;

    public function __construct()
    {
        $this->checkStatus();
    }

    // 检查活动状态
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

    // 后台获取活动列表
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

    // 后台获取活动详情
    public function getInfo(int $activity_id)
    {
        $info = Db::table('activity')
            ->where(['id' => $activity_id])
            ->select(['id', 'bg', 'cover', 'name', 'activity_type', 'organizer', 'is_hot', 'city', 'city_id', 'address', 'lat', 'lon', 'fee',
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

    // 新增活动
    public function add(array $params): int
    {
        return Db::table('activity')->insertGetId($this->generalData($params, true));
    }

    // 编辑活动
    public function edit(array $params): int
    {
        $activity_id = $params['activity_id'];
        return Db::table('activity')
            ->where(['id' => $activity_id])
            ->update($this->generalData($params));
    }

    // 组装活动表数据
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
            'lat' => $data['lat'] ?? '',
            'lon' => $data['lon'] ?? '',
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

    // 根据时间获取活动状态
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

    // 变更活动状态
    public function changeStatus(int $activity_id, int $status): int
    {
        return Db::table('activity')
            ->where(['id' => $activity_id])
            ->update(['status' => $status]);
    }

    // 获取活动参与用户列表（后台)
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
            $item->date = date('m.d', $time);
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
            ->select(['id', 'cover', 'name', 'activity_type', 'active_status', 'fee', 'city', 'address', 'lat', 'lon', 'start_date', 'end_date', 'start_time', 'end_time', 'tags', 'create_time'])
            ->orderBy('is_hot', 'desc')
            ->orderBy('weight', 'desc')
            ->orderBy('create_time', 'desc')
            ->limit(3)
            ->get()
            ->toArray();
        return $this->transformerList($items);
    }

    // 获取城市活动列表
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
        if (!empty($params['lat']) && !empty($params['lon'])) {
            $userLat = (float)$params['lat'];
            $userLon = (float)$params['lon'];
            $earthRadius = 6371;
            $distanceFormula = "ROUND(
                $earthRadius * ACOS(
                    COS(RADIANS($userLat)) * COS(RADIANS(lat)) * 
                    COS(RADIANS(lon) - RADIANS($userLon)) + 
                    SIN(RADIANS($userLat)) * SIN(RADIANS(lat))
                ), 2
            )";
            $query->selectRaw('id,cover,name,activity_type,active_status,fee,city,address,lat,lon,start_date,end_date,start_time,end_time,tags,create_time,'.$distanceFormula.' AS distance');
        } else {
            $query->select(['id', 'cover', 'name', 'activity_type', 'active_status', 'fee', 'city', 'address', 'lat', 'lon', 'start_date', 'end_date', 'start_time', 'end_time', 'tags', 'create_time']);
        }
        $page = !empty($params['page']) ? $params['page'] : 1;
        $page_size = !empty($params['page_size']) ? $params['page_size'] : 15;
        $query->orderBy('weight', 'desc')
            ->orderBy('create_time', 'desc');
        if (!empty($params['lat']) && !empty($params['lon'])) {
            $query->orderBy('distance', 'asc');
        }
        $data = $query->paginate((int)$page_size, page: (int)$page);
        $data = paginateTransformer($data);
        $data['items'] = $this->transformerList($data['items']);
        return $data;
    }

    // 前端列表数据转换
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

    // 活动详情
    public function detail(int $activity_id, int $user_id): object
    {
        $info = Db::table('activity')
            ->where(['id' => $activity_id])
            ->select(['id', 'bg', 'cover', 'name', 'activity_type', 'organizer', 'is_hot', 'city', 'city_id', 'address', 'lat', 'lon', 'fee',
                'start_date', 'end_date', 'start_time', 'end_time', 'weight', 'status', 'active_status', 'tags', 'details', 'create_by', 'create_time'])
            ->first();
        if (empty($info)) {
            throw new ParametersException('活动不存在');
        }
        $info->bg_url = $this->fileService->getFilePathById($info->bg);
        $info->cover_url = $this->fileService->getFilePathById($info->cover);
        $info->tags = json_decode($info->tags, true);
        $info->activity_type_text = ActivityType::from($info->activity_type)->getMessage();
        $info->active_status_text = ActiveStatus::from($info->active_status)->getMessage();
        $info->is_like = $this->checkIsLike($activity_id, $user_id);
        return $info;
    }

    // 想看
    public function like(int $activity_id, int $user_id, int $status): bool
    {
        $has = Db::table('activity_like')
            ->where(['activity_id' => $activity_id, 'user_id' => $user_id])
            ->count();
        if ((empty($has) && $status == 0) || (!empty($has) && $status == 1)) {
            return true;
        }
        Db::beginTransaction();
        try {
            if ($status == 1) {
                $res1 = Db::table('activity_like')->insert([
                    'activity_id' => $activity_id,
                    'user_id' => $user_id,
                    'create_time' => date('Y-m-d H:i:s'),
                ]);
            } else {
                $res1 = Db::table('activity_like')
                    ->where(['activity_id' => $activity_id, 'user_id' => $user_id])
                    ->delete();
            }
            if (!$res1) {
                throw new LogicException('操作失败');
            }
            Db::commit();
        } catch (\Throwable $ex) {
            Db::rollBack();
            throw new ParametersException($ex->getMessage());
        }
        return true;
    }

    public function checkIsLike(int $activity_id, int $user_id): int
    {
        if (empty($user_id) || empty($activity_id)) {
            return 0;
        }
        $has = Db::table('activity_like')
            ->where(['activity_id' => $activity_id, 'user_id' => $user_id])
            ->count();
        return $has > 0 ? 1 : 0;
    }

    public function signUp(int $activity_id, int $user_id)
    {
        $has = Db::table('activity_user')
            ->where(['activity_id' => $activity_id, 'user_id' => $user_id])
            ->first();
        if ($has) {
            throw new LogicException('已报名，请勿重复操作');
        }
        $activity = Db::table('activity')
            ->where('id', $activity_id)
            ->first();
        if (empty($activity)) {
            throw new LogicException('活动不存在');
        }
        if ($activity->active_status == ActiveStatus::ENDED->value) {
            throw new LogicException('活动已结束');
        }
        Db::table('activity_user')->insert([
            'activity_id' => $activity_id,
            'user_id' => $user_id,
            'status' => ActivityUserStatus::JOINED->value,
            'contact_phone' => '',
            'remark' => '',
            'create_time' => date('Y-m-d H:i:s'),
            'update_time' => date('Y-m-d H:i:s'),
        ]);
        return true;
    }

    public function getSignActivityList(array $params = []): array
    {
        $query = Db::table('activity_user')
            ->leftJoin('activity', 'activity.id', '=', 'activity_user.activity_id')
            ->where('activity_user.user_id', $params['user_id'])
            ->where('activity_user.status', ActivityUserStatus::JOINED->value);
        $page = !empty($params['page']) ? $params['page'] : 1;
        $page_size = !empty($params['page_size']) ? $params['page_size'] : 15;
        $data = $query->select(['activity.id as activity_id', 'activity.cover', 'activity.name', 'activity.activity_type',
            'activity.active_status', 'activity.fee', 'activity.city', 'activity.address', 'activity.lat', 'activity.lon',
            'activity.start_date', 'activity.end_date', 'activity.start_time', 'activity.end_time', 'activity.tags'])
            ->orderBy('activity_user.create_time', 'desc')
            ->paginate((int)$page_size, page: (int)$page);
        $data = paginateTransformer($data);
        $data['items'] = $this->transformerList($data['items']);
        return $data;
    }

}
