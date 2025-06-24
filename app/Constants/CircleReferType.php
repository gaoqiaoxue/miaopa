<?php

namespace App\Constants;

use Hyperf\Constants\Annotation\Constants;
use Hyperf\Constants\Annotation\Message;
use Hyperf\Constants\EnumConstantsTrait;

/**
 * 圈子关联类型
 */
#[Constants]
enum CircleReferType: int
{
    use EnumConstantsTrait;

    #[Message('圈子')]
    case CIRCLE = 1;

    #[Message('角色')]
    case ROLE = 2;

}
