<?php

namespace App\Constants;

use Hyperf\Constants\Annotation\Constants;
use Hyperf\Constants\Annotation\Message;
use Hyperf\Constants\EnumConstantsTrait;

/**
 * 帖子类型
 */
#[Constants]
enum PostType: int
{
    use EnumConstantsTrait;

    #[Message('动态帖')]
    case DYNAMIC = 1;

    #[Message('问答帖')]
    case QA = 2;
}