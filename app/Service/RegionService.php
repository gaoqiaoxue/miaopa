<?php

namespace App\Service;

use App\Exception\LogicException;
use App\Exception\ParametersException;
use Hyperf\Cache\Annotation\Cacheable;
use Hyperf\Collection\Arr;
use Hyperf\DbConnection\Db;

class RegionService
{
    public static function getRegionNameById($id, $field = 'name')
    {
        if ($id > 5000) {
            throw new ParametersException('参数错误');
        }
        return Db::table('sys_region')
            ->where('id', $id)
            ->value($field);
    }

    #[Cacheable(prefix: 'region_tree', ttl: 3600)]
    public function getTree(): array
    {
        $all = Db::table('sys_region')
            ->select(['id', 'pid', 'name', 'full_name'])
            ->get()->toArray();
        return arrayToTree($all, 0, 'id', 'pid');
    }

    public function getRegionsByPid(string $pid): array
    {
        if ($pid > 5000) {
            throw new ParametersException('参数错误');
        }
        return Db::table('sys_region')
            ->where('pid', $pid)
            ->select(['id', 'pid', 'name', 'full_name'])
            ->get()->toArray();
    }

    public function getCityInfoByCode($adcode, $region_name = '')
    {
        if (empty($adcode) && !empty($region_name)) {
            $adcode = Db::table('sys_region')
                ->where('name', $region_name)
                ->value('code');
        }
        $region = Db::table('sys_region')
            ->where('code', $adcode)
            ->first();
        if (empty($region) || $region->level == 1) {
            throw new LogicException('无法获取城市信息' . $adcode);
        }
        $region_path = explode('/', $region->path);
        if ($region->level == 2) {
            return [
                'province_code' => $region_path[0] ?? '',
                'city_code' => $region_path[1] ?? '',
                'city_id' => $region->id,
                'city_name' => $region->name,
                'full_name' => $region->full_name,
            ];
        } else {
            return [
                'province_code' => $region_path[0] ?? '',
                'city_code' => $region_path[1] ?? '',
                'region_code' => $region_path[2] ?? '',
                'city_id' => $region->pid,
                'city_name' => explode('/', $region->full_name)[1] ?? '',
                'region_id' => $region->id,
                'region_name' => $region->name,
                'full_name' => $region->full_name,
            ];
        }
    }

    public function getCitys(): array
    {

        $citys = Db::table('sys_region')
            ->where(['level' => 2])
            ->orderBy('pin', 'asc')
            ->orderBy('id', 'desc')
            ->select(['id', 'name', 'pin'])
            ->get()
            ->toArray();
        $list = [];
        foreach ($citys as $city) {
            if (isset($list[$city->pin])) {
                $list[$city->pin]['child'][] = $city;
            } else {
                $list[$city->pin] = [
                    'letter' => $city->pin,
                    'child' => [$city]
                ];
            }
        }
        $list = array_values($list);
        $hot = Db::table('sys_region')
            ->where(['level' => 2, 'is_hot' => 1])
            ->orderBy('sort', 'asc')
            ->orderBy('id', 'desc')
            ->select(['id', 'name', 'pin'])
            ->get()
            ->toArray();
        return returnSuccess(['list' => $list, 'hot' => $hot]);
    }

}