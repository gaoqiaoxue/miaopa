<?php

namespace App\Controller\api;

use App\Controller\AbstractController;
use App\Middleware\ApiMiddleware;
use App\Request\CabinetItemRequest;
use App\Request\CabinetRequest;
use App\Service\CabinetService;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\AutoController;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\Validation\Annotation\Scene;

#[AutoController]
class CabinetController extends AbstractController
{
    #[Inject]
    protected CabinetService $service;


    #[Middleware(ApiMiddleware::class)]
    public function getMyList(): array
    {
        $params = $this->request->all();
        $user_id = $this->request->getAttribute('user_id');
        $params['user_id'] = $user_id;
        $list = $this->service->getList($params);
        return returnSuccess($list);
    }

    public function getList(): array
    {
        $params = $this->request->all();
        if (empty($params['user_id'])) {
            return returnError('缺少必要参数user_id');
        }
        $params['is_public'] = 1;
        $list = $this->service->getList($params);
        return returnSuccess($list);
    }

    #[Scene('id')]
    public function detail(CabinetRequest $request): array
    {
        $cabinet_id = $request->input('cabinet_id', 0);
        $detail = $this->service->getInfo($cabinet_id);
        return returnSuccess($detail);
    }

    #[Scene('add')]
    #[Middleware(ApiMiddleware::class)]
    public function add(CabinetRequest $request): array
    {
        $params = $request->validated();
        $user_id = $this->request->getAttribute('user_id');
        $res = $this->service->add($user_id, $params);
        return returnSuccess(['id' => $res]);
    }

    #[Scene('edit')]
    #[Middleware(ApiMiddleware::class)]
    public function edit(CabinetRequest $request): array
    {
        $params = $request->validated();
        $user_id = $this->request->getAttribute('user_id');
        $this->service->edit($user_id, $params);
        return returnSuccess();
    }

    #[Scene('id')]
    #[Middleware(ApiMiddleware::class)]
    public function delete(CabinetRequest $request): array
    {
        $cabinet_id = $request->input('cabinet_id', 0);
        $user_id = $this->request->getAttribute('user_id');
        $res = $this->service->delete($cabinet_id, $user_id);
        if (!$res) {
            return returnError('删除失败');
        }
        return returnSuccess();
    }

    public function itemList()
    {
        $params = $this->request->all();
        if (empty($params['cabinet_id'])) {
            return returnError('缺少参数');
        }
        $list = $this->service->getItemList($params);
        return returnSuccess($list);
    }

    #[Scene('id')]
    public function itemDetail(CabinetItemRequest $request): array
    {
        $item_id = $request->input('item_id', 0);
        $detail = $this->service->getItemInfo($item_id);
        return returnSuccess($detail);
    }

    #[Scene('add')]
    #[Middleware(ApiMiddleware::class)]
    public function addItem(CabinetItemRequest $request): array
    {
        $params = $request->validated();
        $re = $this->service->addItem($params);
        return returnSuccess(['id' => $re]);
    }

    #[Scene('edit')]
    #[Middleware(ApiMiddleware::class)]
    public function editItem(CabinetItemRequest $request): array
    {
        $params = $request->validated();
        $this->service->editItem($params);
        return returnSuccess();
    }

    #[Scene('id')]
    #[Middleware(ApiMiddleware::class)]
    public function deleteItem(CabinetItemRequest $request): array
    {
        $item_id = $request->input('item_id', 0);
        $this->service->deleteItem($item_id);
        return returnSuccess();
    }


}