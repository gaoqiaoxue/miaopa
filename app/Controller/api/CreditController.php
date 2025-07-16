<?php

namespace App\Controller\api;

use App\Controller\AbstractController;
use App\Middleware\ApiMiddleware;
use App\Service\CreditService;
use App\Service\UserService;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\AutoController;
use Hyperf\HttpServer\Annotation\Middleware;

#[AutoController]
#[Middleware(ApiMiddleware::class)]
class CreditController extends AbstractController
{
    #[Inject]
    protected CreditService $service;

    /**
     * 金币流水
     * @return array
     */
    public function getCoinLog(): array
    {
        $user_id = $this->request->getAttribute('user_id');
        $params = $this->request->all();
        $params['user_id'] = $user_id;
        $list = $this->service->getCoinLogs($params);
        return returnSuccess($list);
    }

    /**
     * 根据页面停留时间获取金币
     * @return array
     */
    public function stay(): array
    {
        $user_id = $this->request->getAttribute('user_id');
        $minute = $this->request->input('minute');
        if(empty($minute)){
            return returnError('请输入停留时间');
        }
        $coins = $this->service->finishStayTask($user_id, $minute);
        return returnSuccess(['coins' => $coins]);
    }

    /**
     * 声望页面
     */
    public function getPrestigeSetting(UserService $userService): array
    {
        $user_id = $this->request->getAttribute('user_id');
        $user = $userService->getInfo($user_id, ['created_days']);
        $prestige = $this->service->getPrestige($user_id);
        $level = $this->service->getPrestigeLevelName($prestige);
        $setting = $this->service->getPrestigeTask($user_id);
        return returnSuccess([
            'user' => $user,
            'prestige' => $prestige,
            'level' => $level,
            'setting' => $setting
        ]);
    }

    /**
     * 声望流水
     * @return array
     */
    public function getPrestigeLog(): array
    {
        $user_id = $this->request->getAttribute('user_id');
        $params = $this->request->all();
        $params['user_id'] = $user_id;
        $list = $this->service->getPrestigeLogs($params);
        return returnSuccess($list);
    }

}