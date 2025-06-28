<?php

namespace App\Service;

use App\Exception\ParametersException;
use Hyperf\DbConnection\Db;

class RegionService
{
    public static function getRegionNameById($id, $field = 'name')
    {
        if($id > 5000){
            throw new ParametersException('参数错误');
        }
        return Db::table('sys_region')
            ->where('id', $id)
            ->value($field);
    }

    public function getTree(): array
    {
        $all = Db::table('sys_region')
            ->select(['id', 'pid', 'name', 'full_name'])
            ->get()->toArray();
        return arrayToTree($all, 0, 'id', 'pid');
    }

    public function getRegionsByPid(string $pid): array
    {
        if($pid > 5000){
            throw new ParametersException('参数错误');
        }
        return Db::table('sys_region')
            ->where('pid', $pid)
            ->select(['id', 'pid', 'name', 'full_name'])
            ->get()->toArray();
    }

}