<?php

namespace App\Controller\admin;

use App\Controller\AbstractController;
use App\Middleware\AdminMiddleware;
use App\Request\CircleRequest;
use App\Service\CircleService;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\AutoController;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\Validation\Annotation\Scene;

#[AutoController]
#[Middleware(AdminMiddleware::class)]
class CircleController extends AbstractController
{
    #[Inject]
    protected CircleService $service;

    public function getList():array
    {
        $params = $this->request->all();
        $list = $this->service->getList($params);
        return returnSuccess($list);
    }

    public function getInfo():array
    {
        $circle_id = $this->request->input('circle_id', 0);
        $circle = $this->service->getInfo($circle_id);
        return returnSuccess($circle);
    }

    #[Scene('add')]
    public function add(CircleRequest $request):array
    {
        $data = $request->validated();
        $result = $this->service->add($data);
        return returnSuccess($result);
    }

    #[Scene('edit')]
    public function edit(CircleRequest $request):array
    {
        $data = $request->validated();
        $result = $this->service->edit($data);
        return returnSuccess($result);
    }

    #[Scene('change_status')]
    public function changeStatus(CircleRequest $request):array
    {
        $data = $request->validated();
        $result = $this->service->changeStatus($data['circle_id'], $data['status']);
        return returnSuccess($result);
    }

}