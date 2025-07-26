<?php
declare(strict_types=1);

namespace App\Task;

use App\Service\CircleStaticsService;
use App\Service\VirtualService;
use Hyperf\Crontab\Annotation\Crontab;
use Hyperf\Di\Annotation\Inject;

#[Crontab(name: "AvatarIconCheck", rule: "0 0 * * *", memo: "检查用户头像挂饰是否失效")]
class AvatarIconCheckTask
{
    #[Inject]
    protected VirtualService $service;

    public function execute()
    {
        $this->service->checkActive();
        logGet('AvatarIconCheckTask', 'task')->info('执行成功');
    }
}
