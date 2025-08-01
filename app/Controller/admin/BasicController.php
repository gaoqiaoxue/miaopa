<?php

namespace App\Controller\admin;

use App\Constants\AbleStatus;
use App\Constants\ActiveStatus;
use App\Constants\ActivityType;
use App\Constants\AuditStatus;
use App\Constants\CircleRelationType;
use App\Constants\CircleType;
use App\Constants\PostType;
use App\Constants\ReportReason;
use App\Constants\ReportType;
use App\Constants\RoleType;
use App\Constants\Sex;
use App\Constants\VirtualType;
use App\Controller\AbstractController;
use App\Service\FileService;
use App\Service\RegionService;
use Hyperf\HttpServer\Annotation\AutoController;

#[AutoController]
class BasicController extends AbstractController
{
    /**
     * 数据字典
     * @return array
     */
    public function dictionary(): array
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
            'file_url' => \Hyperf\Support\env('FILE_HOST')
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
}