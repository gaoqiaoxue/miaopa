<?php

namespace App\Controller\admin;

use App\Controller\AbstractController;
use App\Middleware\AdminMiddleware;
use App\Request\ActivityRequest;
use App\Service\ActivityService;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\AutoController;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\Validation\Annotation\Scene;

#[AutoController]
#[Middleware(AdminMiddleware::class)]
class ActivityController extends AbstractController
{
    #[Inject]
    protected ActivityService $service;

    public function getList(): array
    {
        $params = $this->request->all();
        $list = $this->service->getList($params);
        return returnSuccess($list);
    }

    #[Scene('id')]
    public function getInfo(ActivityRequest $request): array
    {
        $activity_id = $request->input('activity_id', 0);
        $activity = $this->service->getInfo($activity_id,['creater']);
        return returnSuccess($activity);
    }

    #[Scene('add')]
    public function add(ActivityRequest $request): array
    {
        $data = $request->validated();
        $data['create_by'] =  $this->request->getAttribute("user_id");
        $result = $this->service->add($data);
        return returnSuccess(['id' => $result]);
    }

    #[Scene('edit')]
    public function edit(ActivityRequest $request): array
    {
        $data = $request->validated();
        $this->service->edit($data);
        return returnSuccess();
    }

    #[Scene('change_status')]
    public function changeStatus(ActivityRequest $request): array
    {
        $data = $request->validated();
        $this->service->changeStatus($data['activity_id'], $data['status']);
        return returnSuccess();
    }

    #[Scene('id')]
    public function getUsers(ActivityRequest $request): array
    {
        $params = $request->all();
        $list = $this->service->getUsers($params);
        return returnSuccess($list);
    }

}