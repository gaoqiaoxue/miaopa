<?php

namespace App\Constants;

use Hyperf\Constants\Annotation\Constants;
use Hyperf\Constants\Annotation\Message;
use Hyperf\Constants\EnumConstantsTrait;

/**
 * 后台用户/角色状态
 */
#[Constants]
enum SysStatus: int
{
    use EnumConstantsTrait;

    #[Message('启用')]
    case ENABLE = 0;

    #[Message('禁用')]
    case DISABLE = 1;

}
