<?php
declare(strict_types=1);

namespace App\Task;

use App\Service\ActivityService;
use Hyperf\Crontab\Annotation\Crontab;
use Hyperf\Di\Annotation\Inject;

#[Crontab(name: "ActivityStatusCheck", rule: "*/5 * * * *", memo: "活动状态变更")]
class ActivityStatusCheckTask
{
    #[Inject]
    protected ActivityService $service;

    public function execute()
    {
        $this->service->checkStatus();
        logGet('ActivityStatusCheckTask', 'default')->info('执行成功');
    }
}
