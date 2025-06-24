<?php

namespace App\Constants;

use Hyperf\Constants\Annotation\Constants;
use Hyperf\Constants\Annotation\Message;
use Hyperf\Constants\EnumConstantsTrait;

/**
 * 活动类型
 */
#[Constants]
enum ActivityType: int
{
    use EnumConstantsTrait;

    #[Message('漫展')]
    case COMIC_CON = 1;

}
