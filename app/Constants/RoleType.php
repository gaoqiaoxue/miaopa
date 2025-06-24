<?php

namespace App\Constants;

use Hyperf\Constants\Annotation\Constants;
use Hyperf\Constants\Annotation\Message;
use Hyperf\Constants\EnumConstantsTrait;

/**
 * 角色类型
 */
#[Constants]
enum RoleType: int
{
    use EnumConstantsTrait;

    #[Message('动漫角色')]
    case CARTOON = 1;

    #[Message('游戏角色')]
    case GAME = 2;
}
