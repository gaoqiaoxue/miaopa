<?php

namespace App\Controller\admin;

use App\Constants\ActivityType;
use App\Constants\CircleReferType;
use App\Constants\CircleType;
use App\Constants\PostType;
use App\Constants\RoleType;
use App\Constants\Sex;
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
            'circle_type' => CircleType::getMaps(),
            'circle_refer_type' => CircleReferType::getMaps(),
            'post_type' => PostType::getMaps(),
            'role_type' => RoleType::getMaps(),
            'activity_type' => ActivityType::getMaps(),
            'virtual_type' => VirtualType::getMaps(),
            'sex' => Sex::getMaps()
        ];
    }

    public function upload(FileService $service)
    {
        $file = $this->request->file('file');
        $info = $service->upload($file);
        return returnSuccess($info);
    }
}