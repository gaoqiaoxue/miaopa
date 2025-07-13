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

use Hyperf\Testing\TestCase;

/**
 * @internal
 * @coversNothing
 */
class ExampleTest extends TestCase
{
    public function testExample()
    {

        logGet('mediaCheck','wxmini')->debug(json_encode([
            'media_url' => '111',
            'media_type' => 2, // 1:音频;2:图片
            'res' => [
                'status' => 1,
                'mes' => '23e23'
            ]
        ]));


        $this->assertTrue(true, 'kthis');
//        $this->get('/')->assertOk()->assertSee('Hyperf');
    }
}
