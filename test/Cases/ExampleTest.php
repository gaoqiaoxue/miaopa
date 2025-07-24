<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

namespace HyperfTest\Cases;

use App\Constants\AbleStatus;
use App\Constants\IsRisky;
use App\Library\WechatMiniAppLib;
use App\Service\CircleService;
use App\Service\PostsService;
use Hyperf\DbConnection\Db;
use Hyperf\Testing\TestCase;
use function Hyperf\Support\make;

/**
 * @internal
 * @coversNothing
 */
class ExampleTest extends TestCase
{
    public function testExample()
    {
        $sql = "SELECT 
    c.id, c.name, c.cover, c.relation_type, c.relation_ids,
    CASE WHEN cf.user_id IS NOT NULL THEN 1 ELSE 0 END AS is_follow
FROM 
    mp_circle c
LEFT JOIN 
    mp_circle_follow cf ON c.id = cf.circle_id AND cf.user_id = :userId
WHERE 
    c.status = :status
ORDER BY 
    is_follow DESC, 
    c.is_hot DESC, 
    c.weight DESC, 
    c.id DESC
LIMIT 10";
        $circles = Db::select($sql, ['userId' => 1, 'status' => AbleStatus::ENABLE->value]);
        var_dump($circles);
        $this->assertTrue(true, '111');
    }
}
