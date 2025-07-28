<?php
declare(strict_types=1);

namespace App\Task;

use App\Service\CircleStaticsService;
use App\Service\UserStaticsService;
use Hyperf\Crontab\Annotation\Crontab;
use Hyperf\Di\Annotation\Inject;

#[Crontab(name: "PersistUserStats", rule: "0 0 * * *", memo: "持久化用户、访客数量")]
class PersistUserStatsTask
{
    #[Inject]
    protected UserStaticsService $statService;

    public function execute()
    {
        $this->statService->persistYesterdayStats();
        logGet('PersistUserStatsTask', 'task')->info('执行成功');
    }
}
