<?php
declare(strict_types=1);

namespace App\Task;

use App\Service\CircleStaticsService;
use Hyperf\Crontab\Annotation\Crontab;
use Hyperf\Di\Annotation\Inject;

#[Crontab(name: "PersistCircleStats", rule: "0 0 * * *", memo: "持久化圈子点击量")]
class PersistCircleStatsTask
{
    #[Inject]
    protected CircleStaticsService $statService;

    public function execute()
    {
        $this->statService->persistDailyStats();
        logGet('PersistCircleStatsTask', 'task')->info('执行成功');
    }
}
