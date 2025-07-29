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
        $url = 'http://sns-video-bd.xhscdn.com/spectrum/1040g35831k1ojd5j2a005nue3ung8q1fbr24kc8';
        $service = make(FileService::class);
        $res = $service->saveFileToOss($url,'xiaohongshu/videos','mp4');
        var_dump($res);
        $this->assertTrue(true, '111');
    }
}
