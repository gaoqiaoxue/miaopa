<?php

namespace App\Controller\api;

use App\Constants\PostType;
use App\Controller\AbstractController;
use App\Library\Contract\AuthTokenInterface;
use App\Library\Contract\MapWebInterface;
use App\Service\ActivityService;
use App\Service\CircleService;
use App\Service\PostsService;
use App\Service\UserService;
use Hyperf\HttpServer\Annotation\AutoController;

#[AutoController]
class IndexController extends AbstractController
{
    public function index(MapWebInterface $service)
    {
        return [
            'data' => $this->request->all()
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
            'circle' => $circleService->getSelect(['name' => $keyword, 'status' => 1], ['id', 'name', 'cover'], 6),
            'user' => $userService->getApiList(['keyword' => $keyword, 'user_id' => $user_id], false, 6),
            'activity' => $activityService->getApiSelect(['keyword' => $keyword], 6),
            'qa' => $postsService->getApiList(['keyword' => $keyword, 'post_type' => PostType::QA->value,'current_user_id' => $user_id],false, 6),
            'dynamic' => $postsService->getApiList(['keyword' => $keyword, 'post_type' => PostType::DYNAMIC->value,'current_user_id' => $user_id],false, 6),
        ];
        return returnSuccess($result);
    }

}