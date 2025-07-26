<?php

namespace App\Controller\admin;

use App\Controller\AbstractController;
use App\Middleware\AdminMiddleware;
use App\Service\AuditService;
use App\Service\CircleStaticsService;
use Hyperf\HttpServer\Annotation\AutoController;
use Hyperf\HttpServer\Annotation\Middleware;

#[AutoController]
#[Middleware(AdminMiddleware::class)]
class IndexController extends AbstractController
{
    public function index(): array
    {
        // TODO 统计数据
        return returnSuccess([
            'guest' => [
                'today' => 100,
                'compare_yesterday' => 10,
                'compare_status' => 1,
            ],
            'user' => [
                'today' => 100,
                'compare_yesterday' => 10,
                'compare_status' => 0,
            ],
            'register_rate' => [
                'today' => 3.85,
                'compare_yesterday' => 0.5,
                'compare_status' => 1,
            ],
            'activity' => [
                'today' => 100,
                'compare_yesterday' => 10,
                'compare_status' => 1,
            ]
        ]);
    }

    public function auditMessage(AuditService $service)
    {
        $list = $service->getAdminAuditMessage();
        return returnSuccess($list);
    }

    public function activeUserTrend()
    {
        $type = $this->request->input('type', 1);
        if ($type == 1) {
            $list = [
                ['time' => '00:00', 'guest' => '100', 'user' => '80'],
                ['time' => '02:00', 'guest' => '100', 'user' => '80'],
                ['time' => '04:00', 'guest' => '100', 'user' => '80'],
                ['time' => '06:00', 'guest' => '100', 'user' => '80'],
                ['time' => '08:00', 'guest' => '100', 'user' => '80'],
                ['time' => '10:00', 'guest' => '100', 'user' => '80'],
                ['time' => '12:00', 'guest' => '100', 'user' => '80'],
                ['time' => '14:00', 'guest' => '100', 'user' => '80'],
                ['time' => '16:00', 'guest' => '100', 'user' => '80'],
                ['time' => '18:00', 'guest' => '100', 'user' => '80'],
                ['time' => '20:00', 'guest' => '100', 'user' => '80'],
                ['time' => '22:00', 'guest' => '100', 'user' => '80'],
                ['time' => '00:00', 'guest' => '100', 'user' => '80'],
            ];
        } elseif ($type == 7) {
            for ($i = 7; $i >= 1; $i--) {
                $list[] = [
                    'time' => date('Y-m-d', strtotime('-' . $i . ' day')),
                    'guest' => '100',
                    'user' => '80',
                ];
            }
        } elseif ($type == 15) {
            for ($i = 15; $i >= 1; $i--) {
                $list[] = [
                    'time' => date('Y-m-d', strtotime('-' . $i . ' day')),
                    'guest' => '100',
                    'user' => '80',
                ];
            }
        } else {
            return returnError('type错误');
        }
        return returnSuccess($list);
    }

    public function postTrend()
    {
        $type = $this->request->input('type', 1);
        if ($type == 1) {
            $list = [
                ['time' => '00:00', 'count' => '15'],
                ['time' => '02:00', 'count' => '12'],
                ['time' => '04:00', 'count' => '25'],
                ['time' => '06:00', 'count' => '13'],
                ['time' => '08:00', 'count' => '36'],
                ['time' => '10:00', 'count' => '88'],
                ['time' => '12:00', 'count' => '107'],
                ['time' => '14:00', 'count' => '66'],
                ['time' => '16:00', 'count' => '85'],
                ['time' => '18:00', 'count' => '120'],
                ['time' => '20:00', 'count' => '245'],
                ['time' => '22:00', 'count' => '235'],
                ['time' => '00:00', 'count' => '66'],
            ];
        } elseif ($type == 7) {
            for ($i = 7; $i >= 1; $i--) {
                $list[] = [
                    'time' => date('Y-m-d', strtotime('-' . $i . ' day')),
                    'count' => '66',
                ];
            }
        } elseif ($type == 15) {
            for ($i = 15; $i >= 1; $i--) {
                $list[] = [
                    'time' => date('Y-m-d', strtotime('-' . $i . ' day')),
                    'count' => '66',
                ];
            }
        } else {
            return returnError('type错误');
        }
        return returnSuccess($list);
    }

    public function circleTrend(CircleStaticsService $service)
    {
        $type = $this->request->input('type', 1);
        if($type == 1){
            $list = $service->getDailyRankingWithTrend();
        }else{
            $list = $service->getPeriodRankingWithTrend(7);
        }
        return returnSuccess($list);
    }
}