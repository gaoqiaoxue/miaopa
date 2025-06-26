<?php

namespace App\Controller\admin;

use App\Constants\ActivityType;
use App\Constants\CircleReferType;
use App\Constants\CircleType;
use App\Constants\PostType;
use App\Constants\RoleType;
use App\Constants\VirtualType;
use App\Controller\AbstractController;
use App\Service\FileService;
use Hyperf\HttpServer\Annotation\AutoController;

#[AutoController]
class BasicController extends AbstractController
{
    /**
     * 数据字典
     * @return array
     */
    public function dictionary()
    {
        return [
            'circle_type' => getEnumMaps(CircleType::class),
            'circle_refer_type' => getEnumMaps(CircleReferType::class),
            'post_type' => getEnumMaps(PostType::class),
            'role_type' => getEnumMaps(RoleType::class),
            'activity_type' => getEnumMaps(ActivityType::class),
            'virtual_type' => getEnumMaps(VirtualType::class),
        ];
    }

    public function upload(FileService $service)
    {
        $file = $this->request->file('file');
        $info = $service->upload($file);
        return returnSuccess($info);
    }
}