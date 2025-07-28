<?php

namespace App\Controller\api;

use App\Controller\AbstractController;
use App\Middleware\ApiBaseMiddleware;
use App\Middleware\ApiMiddleware;
use App\Service\VirtualService;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\AutoController;
use Hyperf\HttpServer\Annotation\Middleware;

#[AutoController]
#[Middleware(ApiBaseMiddleware::class)]
class VirtualController extends AbstractController
{
    #[Inject]
    protected VirtualService $service;

    #[Middleware(ApiMiddleware::class)]
    public function getCurrent(): array
    {
        $user_id = $this->request->getAttribute('user_id');
        $current = $this->service->getCurrent($user_id);
        return returnSuccess($current);
    }

    public function getList(): array
    {
        $params = $this->request->all();
        $params['status'] = 1;
        $params['is_default'] = 0;
        $params['current_user_id'] = $this->request->getAttribute('user_id', 0);
        $params['check_exchange'] = true;
        $list = $this->service->getList($params);
        return returnSuccess($list);
    }

    public function detail()
    {
        $item_id = $this->request->input('item_id', 0);
        if (empty($item_id)) {
            return returnError('缺少必要参数item_id');
        }
        $detail = $this->service->getInfo($item_id);
        return returnSuccess($detail);
    }

    #[Middleware(ApiMiddleware::class)]
    public function exchange(): array
    {
        $user_id = $this->request->getAttribute('user_id');
        $item_id = $this->request->input('item_id', 0);
        if (empty($item_id)) {
            return returnError('缺少必要参数item_id');
        }
        $this->service->exchange($user_id, $item_id);
        return returnSuccess([], '兑换成功');
    }

    #[Middleware(ApiMiddleware::class)]
    public function exchangeList(): array
    {
        $user_id = $this->request->getAttribute('user_id');
        $params = $this->request->all();
        $params['user_id'] = $user_id;
        $list = $this->service->getExchangeList($params);
        return returnSuccess($list);
    }

    #[Middleware(ApiMiddleware::class)]
    public function active(): array
    {
        $user_id = $this->request->getAttribute('user_id');
        $exchange_id = $this->request->input('exchange_id', 0);
        if (empty($exchange_id)) {
            return returnError('缺少必要参数exchange_id');
        }
        $this->service->active($user_id, $exchange_id);
        return returnSuccess([], '操作成功');
    }

    #[Middleware(ApiMiddleware::class)]
    public function cancel(): array
    {
        $user_id = $this->request->getAttribute('user_id');
        $exchange_id = $this->request->input('exchange_id', 0);
        if (empty($exchange_id)) {
            return returnError('缺少必要参数exchange_id');
        }
        $this->service->cancel($user_id, $exchange_id);
        return returnSuccess([], '操作成功');
    }

    #[Middleware(ApiMiddleware::class)]
    public function avatarSetting()
    {
        $user_id = $this->request->getAttribute('user_id');
        $params = $this->request->all();
        $this->service->avatarSetting($user_id, $params);
        return returnSuccess([], '操作成功');
    }
}