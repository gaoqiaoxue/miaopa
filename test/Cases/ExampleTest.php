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

use App\Service\FileService;
use App\Service\ImageService;
use App\Service\XiaohongshuService;
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
        $users = Db::table('xhs_notes')
            ->where('user_id', '>', 0)
            ->pluck('user_id')
            ->toArray();
        foreach ($users as $user_id){
            $has = Db::table('user_credit')->where(['user_id' => $user_id])->count();
            if(!$has){
                Db::table('user_credit')->insert(['user_id' => $user_id]);
            }
        }
        $this->assertTrue(true, '1111');
    }

    public function testExampleCircle()
    {
        $service = make(XiaohongshuService::class);
        $res = $service->saveToCircle();
        var_dump($res);
        $this->assertTrue(true, 'circle');
    }

    public function testExampleUser()
    {
        $service = make(XiaohongshuService::class);
        $res = 1;
        while ($res){
            $res = $service->saveToUser();
        }
        $this->assertTrue(true, '111');
    }

    public function testExamplePost()
    {
        $service = make(XiaohongshuService::class);
        $res = 1;
        while ($res){
            $res = $service->saveToNormalPost();
        }
        var_dump($res);
        $this->assertTrue(true, '111');
    }
}
