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
        $service = make(PostsService::class);
        $res = $service->getApiList(['is_reported' => 0, 'audit_status' => 1], false, 3, true);
        var_dump($res);
        $this->assertTrue(true, '111');
    }
}
