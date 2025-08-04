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

use App\Constants\AuditStatus;
use App\Service\ConfigService;
use App\Service\FileService;
use App\Service\ImageService;
use App\Service\PostsService;
use App\Service\XiaohongshuService;
use App\Service\ZDouYinService;
use App\Service\ZhihuService;
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
//        $file = BASE_PATH.'/data/douyin.json';
        $service = make(ZDouYinService::class);
        $res = 1;
        while ($res){
            $res = $service->transPost();
        }
        $this->assertTrue(true, '1111');
    }

//    public function testExampleCircle()
//    {
//        $service = make(XiaohongshuService::class);
//        $res = $service->saveToCircle();
//        var_dump($res);
//        $this->assertTrue(true, 'circle');
//    }
//
//    public function testExampleUser()
//    {
//        $service = make(XiaohongshuService::class);
//        $res = 1;
//        while ($res){
//            $res = $service->saveToUser();
//        }
//        $this->assertTrue(true, '111');
//    }
//
//    public function testExamplePost()
//    {
//        $service = make(XiaohongshuService::class);
//        $res = 1;
//        while ($res){
//            $res = $service->saveToNormalPost();
//        }
//        var_dump($res);
//        $this->assertTrue(true, '111');
//    }
}
