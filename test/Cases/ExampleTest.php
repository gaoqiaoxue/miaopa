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
        $users = Db::table('user')
            ->where('id','>', 10)
            ->get(['id', 'avatar'])
            ->toArray();
        foreach ($users as $user){
            Db::table('xhs_notes')
                ->where('user_id', $user->id)
                ->update(['auther_avatar' => $user->avatar]);
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
