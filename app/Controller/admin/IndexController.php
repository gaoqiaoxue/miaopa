<?php

namespace App\Controller\admin;

use App\Controller\AbstractController;
use App\Middleware\AdminMiddleware;
use App\Service\ActivityService;
use App\Service\AuditService;
use App\Service\CircleStaticsService;
use App\Service\PostsService;
use App\Service\UserStaticsService;
use Hyperf\HttpServer\Annotation\AutoController;
use Hyperf\HttpServer\Annotation\Middleware;

#[AutoController]
#[Middleware(AdminMiddleware::class)]
class IndexController extends AbstractController
{
    public function index(UserStaticsService $userStaticsService, ActivityService $activityService): array
    {
        $user_statics = $userStaticsService->getUserStatics();
        $activityService = $activityService->getActivityStatics();
        return returnSuccess([
            'guest' => $user_statics['guest'],
            'user' => $user_statics['user'],
            'register_rate' => $user_statics['register_rate'],
            'activity' => $activityService
        ]);
    }

    public function auditMessage(AuditService $service)
    {
        $list = $service->getAdminAuditMessage();
        return returnSuccess($list);
    }

    public function activeUserTrend(UserStaticsService $service)
    {
        $type = $this->request->input('type', 1);
        if ($type == 1) {
            $list = $service->getTodayHourlyStats();
        } elseif ($type == 7 || $type == 15) {
            $list = $service->getMultiDaySummaryStats((int)$type);
        } else {
            return returnError('type错误');
        }
        return returnSuccess($list);
    }

    public function postTrend(PostsService $service)
    {
        $type = $this->request->input('type', 1);
        if ($type == 1) {
            $list = $service->dailyPublishStatics();
        } elseif ($type == 7 || $type == 15) {
            $list = $service->daysPublishStatics((int)$type);
        } else {
            return returnError('type错误');
        }
        return returnSuccess($list);
    }

    public function circleTrend(CircleStaticsService $service)
    {
        $type = $this->request->input('type', 1);
        if ($type == 1) {
            $list = $service->getDailyRankingWithTrend();
        } else {
            $list = $service->getPeriodRankingWithTrend($type);
        }
        return returnSuccess($list);
    }
}