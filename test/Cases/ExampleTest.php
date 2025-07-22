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
        $service = make(CircleService::class);
        $res = $service->getRelationsById(1);
        var_dump($res);
        $this->assertTrue(true, '111');
    }
}
