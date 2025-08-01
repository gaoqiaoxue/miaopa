<?php

namespace App\Controller\api;

use App\Constants\AbleStatus;
use App\Constants\ActiveStatus;
use App\Constants\ActivityType;
use App\Constants\AuditStatus;
use App\Constants\CabinetType;
use App\Constants\CircleRelationType;
use App\Constants\CircleType;
use App\Constants\PostType;
use App\Constants\ReportReason;
use App\Constants\ReportType;
use App\Constants\RoleType;
use App\Constants\Sex;
use App\Constants\VirtualType;
use App\Controller\AbstractController;
use App\Library\Contract\MapWebInterface;
use App\Middleware\ApiBaseMiddleware;
use App\Service\ConfigService;
use App\Service\FileService;
use App\Service\RegionService;
use Hyperf\HttpServer\Annotation\AutoController;
use Hyperf\HttpServer\Annotation\Middleware;

#[AutoController]
#[Middleware(ApiBaseMiddleware::class)]
class BasicController extends AbstractController
{
    /**
     * 数据字典
     * @return array
     */
    public function dictionary(ConfigService $service): array
    {
        return returnSuccess([
            'status' => AbleStatus::getMaps(),
            'circle_type' => CircleType::getMaps(),
            'circle_relation_type' => CircleRelationType::getMaps(),
            'post_type' => PostType::getMaps(),
            'role_type' => RoleType::getMaps(),
            'activity_type' => ActivityType::getMaps(),
            'activity_status' => ActiveStatus::getMaps(),
            'item_type' => VirtualType::getMaps(),
            'sex' => Sex::getMaps(),
            'audit_status' => AuditStatus::getMaps(),
            'report_type' => ReportType::getMaps(),
            'report_reason' => ReportReason::getMaps(),
            'cabinet_type' => CabinetType::getMaps(),
            'file_url' => \Hyperf\Support\env('FILE_HOST'),
            'stay_time_config' => $service->getValue('stay_time_config'),
            'kefu_mobile' => ''
        ]);
    }

    public function upload(FileService $service): array
    {
        $file = $this->request->file('file');
        $info = $service->upload($file);
        return returnSuccess($info);
    }

    public function getRegionTree(RegionService $service): array
    {
        $tree = $service->getTree();
        return returnSuccess($tree);
    }

    public function getRegionsByPid(RegionService $service): array
    {
        $pid = $this->request->input('pid', 0);
        $regions = $service->getRegionsByPid($pid);
        return returnSuccess($regions);
    }


    /**
     * 获取城市列表（按拼音首字母）
     * @return array
     */
    public function getCitys(RegionService $service): array
    {
        $params = $this->request->all();
        $regions = $service->getCitys($params);
        return returnSuccess($regions);
    }

    /**
     * 根据经纬度获取定位城市ID
     */
    public function getCityByLatLon(MapWebInterface $service): array
    {
        $params = $this->request->all();
        if (empty($params['lat']) || empty($params['lon'])) {
            return returnError('请传入经纬度');
        }
        $info = $service->getRegionInfoByLonLat($params['lon'], $params['lat']);
        return returnSuccess($info);
    }

    /**
     * 获取用户协议
     * @param ConfigService $configService
     * @return array
     */
    public function agreement(ConfigService $configService): array
    {
        $key = $this->request->input('key', 'user_agreement');
        $config = $configService->getValue($key);
        return returnSuccess($config);
    }

}