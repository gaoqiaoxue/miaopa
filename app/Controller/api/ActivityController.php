<?php

namespace App\Controller\api;

use App\Controller\AbstractController;
use App\Service\ActivityService;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\AutoController;

#[AutoController]
class ActivityController extends AbstractController
{
    #[Inject]
    protected ActivityService $service;

    public function index(): array
    {
        $city_id = $this->request->input('city_id', 0);
        return returnSuccess([
            'hot' => $this->service->getHot((int)$city_id),
            'dates' => $this->service->getDates((int)$city_id),
        ]);
    }

    public function getList(): array
    {
        $params = $this->request->all();
        $list = $this->service->getApiList($params);
        return returnSuccess($list);
    }

}