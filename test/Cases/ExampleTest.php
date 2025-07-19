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
        $trace_id = '687b5b2f-246bab54-79a586b4';
        $is_risky = 2;
        $info = Db::table('sys_media_audit')->where('trace_id', $trace_id)->first();
//        if (empty($info)) {
//            return true;
//        }
//        Db::table('sys_media_audit')->where('trace_id', $trace_id)->update([
//            'is_risky' => $is_risky,
//            'update_time' => date('Y-m-d H:i:s'),
//        ]);
//        Db::table('sys_upload')->where('trace_id', $trace_id)->update([
//            'is_risky' => $is_risky
//        ]);
        if ($is_risky == IsRisky::SAFE->value && ($info->type == 'avatar' || $info->type == 'bg')) {
            var_dump('1231233');
            Db::table('user')->where('id', $info->user_id)->update([
                $info->type => $info->url,
                'update_time' => date('Y-m-d H:i:s'),
            ]);
        }else{
            var_dump('1231234');
        }

        $this->assertTrue(true, '111');
//        $this->get('/')->assertOk()->assertSee('Hyperf');
    }
}
