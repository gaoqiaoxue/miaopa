<?php

namespace App\Constants;

use Hyperf\Constants\Annotation\Constants;
use Hyperf\Constants\Annotation\Message;
use Hyperf\Constants\EnumConstantsTrait;

#[Constants]
enum ArticleType: int
{
    use EnumConstantsTrait;

    #[Message('圈子')]
    case ARTICLE = 1;

    #[Message('动漫IP')]
    case CARTOON = 2;

    #[Message('游戏IP')]
    case GAME = 3;
}
