<?php

namespace App\Controller\api;

use App\Constants\AuditStatus;
use App\Constants\IsRisky;
use App\Constants\PostType;
use App\Controller\AbstractController;
use App\Middleware\ApiBaseMiddleware;
use App\Service\ActivityService;
use App\Service\CircleService;
use App\Service\ConfigService;
use App\Service\MediaAuditService;
use App\Service\PostsService;
use App\Service\UserService;
use Hyperf\HttpServer\Annotation\AutoController;
use Hyperf\HttpServer\Annotation\Middleware;

#[AutoController]
#[Middleware(ApiBaseMiddleware::class)]
class IndexController extends AbstractController
{
    public function test()
    {
        $core_id = $this->request->getHeaderLine('coreId');
        return [
            'user_id' => $this->request->getAttribute('user_id', 0),
            'core_id' => $core_id,
        ];
    }

    public function search(
        CircleService      $circleService,
        PostsService       $postsService,
        ActivityService    $activityService,
        UserService        $userService,
        ConfigService      $configService
    )
    {
        $keyword = $this->request->input('keyword');
        if (empty($keyword)) {
            return returnError('请输入搜索内容');
        }
        $user_id = $this->request->getAttribute('user_id', 0);
        $config = $configService->getConfig();
        $result = [
            'circle' => $circleService->getSelect([
                'name' => $keyword,
                'status' => 1,
                'cate' => ['is_follow'],
                'user_id' => $user_id
            ], ['id', 'name', 'cover'], 6),
            'user' => $userService->getApiList([
                'keyword' => $keyword,
                'current_user_id' => $user_id
            ], false, 3),
            'activity' => $activityService->getApiList([
                'keyword' => $keyword
            ], false,1),
            'qa' => $postsService->getApiList([
                'keyword' => $keyword,
                'post_type' => PostType::QA->value,
                'is_reported' => 0,
                'post_publish_type' => $config->post_publish_type,
                'report_publish_type' => $config->report_publish_type,
                'current_user_id' => $user_id
            ], false, 3, true),
            'dynamic' => $postsService->getApiList([
                'keyword' => $keyword,
                'post_type' => PostType::DYNAMIC->value,
                'is_reported' => 0,
                'post_publish_type' => $config->post_publish_type,
                'report_publish_type' => $config->report_publish_type,
                'current_user_id' => $user_id
            ], false, 15, true),
        ];
        return returnSuccess($result);
    }

    public function wxnotity(MediaAuditService $mediaAuditService)
    {
        $param = $this->request->all();
        logGet('wxnotity', 'wxmini')->debug(json_encode($param));
        if (isset($param['echostr'])) {
            return $param['echostr'];
        } elseif (isset($param['MsgType']) && $param['MsgType'] == 'event' && $param['Event'] == 'wxa_media_check') {
            $trace_id = $param['trace_id'];
            $is_risky = isset($param['result']['suggest']) && $param['result']['suggest'] == 'pass' ? IsRisky::SAFE->value : IsRisky::RISKY->value;
            $mediaAuditService->updateMediaAudit($trace_id, $is_risky);
            return 'success';
        }
        return 'fail';
    }

}