<?php

namespace App\Controller\admin;

use App\Controller\AbstractController;
use App\Middleware\AdminMiddleware;
use App\Request\ReportRequest;
use App\Service\ReportService;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\AutoController;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\Validation\Annotation\Scene;

/**
 * 举报
 */
#[AutoController()]
#[Middleware(AdminMiddleware::class)]
class ReportController extends AbstractController
{
    #[Inject]
    protected ReportService $service;

    public function getAuditList(ReportRequest $request): array
    {
        $params = $this->request->all();
        $list = $this->service->getAuditList($params);
        return returnSuccess($list);
    }


    #[Scene('pass')]
    public function pass(ReportRequest $request): array
    {
        $data = $request->validated();
        $user_id = $this->request->getAttribute('user_id');
        $this->service->pass($data['report_id'],$user_id, $data['mute_time'] ?? 0);
        return returnSuccess();
    }

    #[Scene('reject')]
    public function reject(ReportRequest $request): array
    {
        $data = $request->validated();
        $user_id = $this->request->getAttribute('user_id');
        $this->service->reject($data['report_id'],$user_id,$data['reject_reason']);
        return returnSuccess();

    }

}