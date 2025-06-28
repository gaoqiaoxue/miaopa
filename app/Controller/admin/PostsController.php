<?php

namespace App\Controller\admin;

use App\Controller\AbstractController;
use App\Middleware\AdminMiddleware;
use App\Request\VirtualRequest;
use App\Service\VirtualService;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\AutoController;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\Validation\Annotation\Scene;

#[AutoController()]
#[Middleware(AdminMiddleware::class)]
class PostsController extends AbstractController
{
    #[Inject]
    protected VirtualService $service;

    public function getList(): array
    {
        $params = $this->request->all();
        $list = $this->service->getList($params);
        return returnSuccess($list);
    }

    #[Scene('id')]
    public function getInfo(VirtualRequest $request): array
    {
        $virtual_id = $request->input('virtual_id', 0);
        $virtual = $this->service->getInfo($virtual_id);
        return returnSuccess($virtual);
    }

    #[Scene('add')]
    public function add(VirtualRequest $request): array
    {
        $data = $request->validated();
        $data['create_by'] = $this->request->getAttribute("user_id");
        $result = $this->service->add($data);
        return returnSuccess(['id' => $result]);
    }

    #[Scene('edit')]
    public function edit(VirtualRequest $request): array
    {
        $data = $request->validated();
        $this->service->edit($data);
        return returnSuccess();
    }

    #[Scene('id')]
    public function delete(VirtualRequest $request): array
    {
        $activity_id = $request->input('virtual_id', 0);
        $this->service->delete($activity_id);
        return returnSuccess();
    }

}