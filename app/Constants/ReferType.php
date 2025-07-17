<?php

namespace App\Constants;

use Hyperf\Constants\Annotation\Constants;
use Hyperf\Constants\Annotation\Message;
use Hyperf\Constants\EnumConstantsTrait;

/**
 * 关联类型
 */
#[Constants]
enum ReferType: int
{
    use EnumConstantsTrait;

    #[Message('帖子')]
    case POST = 1;

    #[Message('评论')]
    case COMMENT = 2;

    #[Message('活动')]
    case ACTIVITY = 3;

}
