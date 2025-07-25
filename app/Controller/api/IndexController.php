<?php

namespace App\Controller\api;

use App\Constants\AuditStatus;
use App\Constants\IsRisky;
use App\Constants\PostType;
use App\Controller\AbstractController;
use App\Library\Contract\AuthTokenInterface;
use App\Service\ActivityService;
use App\Service\CircleService;
use App\Service\MediaAuditService;
use App\Service\PostsService;
use App\Service\UserService;
use App\Service\XiaohongshuService;
use Hyperf\DbConnection\Db;
use Hyperf\HttpServer\Annotation\AutoController;

#[AutoController]
class IndexController extends AbstractController
{
    public function test()
    {
        $nickname = '';
        for ($i = 0; $i < 6; $i++) {
            $nickname .= chr(mt_rand(97, 122));
        }
        $data = $this->request->getHeaders();
//        $core_id = $this->request->getHeaderLine('coreId');
        return [
            'data' => $data,
            'core_id' => $nickname,
        ];
    }

    public function xhs_list(XiaohongshuService $service)
    {
        $params = $this->request->all();
        $file_path = BASE_PATH . '/data/search_contents_2025-07-19-1.json';
        return [
            'data' => $service->saveJson($file_path)
//            'data' => $service->searchAndSave($params['keyword'],$params['page'],$params['page_size'] ?? 20)
        ];
    }

    public function xhs_get()
    {
        $params = $this->request->all();
        var_dump($params);
        $limit = is_numeric($params['input']) ? $params['input'] : 20;
        return [
            'data' => Db::table('xhs_notes')
                ->where('is_detail', 0)
                ->limit($limit)
//                ->orderByDesc('id')
                ->get(['note_id', 'note_url'])
        ];
    }

    public function xhs(XiaohongshuService $service)
    {
        $params = $this->request->all();
//        foreach ($params['input'] as $param){
        $service->saveCozeData($params['input']);
//        }
        $s = rand(25, 40);
        var_dump($s);
        sleep($s);
//        logGet('xiaohongshu')->info(json_encode($params['input']));
        return [
            'data' => $params
        ];
    }

    public function search(
        CircleService      $circleService,
        PostsService       $postsService,
        ActivityService    $activityService,
        UserService        $userService,
        AuthTokenInterface $authToken
    )
    {
        $keyword = $this->request->input('keyword');
        if (empty($keyword)) {
            return returnError('请输入搜索内容');
        }
        $payload = $authToken->getUserData('default', false);
        $user_id = $payload['jwt_claims']['user_id'] ?? 0;
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
            'activity' => $activityService->getApiSelect([
                'keyword' => $keyword
            ], 1),
            'qa' => $postsService->getApiList([
                'keyword' => $keyword,
                'post_type' => PostType::QA->value,
                'is_reported' => 0,
                'audit_status' => AuditStatus::PASSED->value,
                'current_user_id' => $user_id
            ], false, 3, true),
            'dynamic' => $postsService->getApiList([
                'keyword' => $keyword,
                'post_type' => PostType::DYNAMIC->value,
                'is_reported' => 0,
                'audit_status' => AuditStatus::PASSED->value,
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