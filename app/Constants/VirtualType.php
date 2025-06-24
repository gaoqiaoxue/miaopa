<?php

namespace App\Constants;

use Hyperf\Constants\Annotation\Constants;
use Hyperf\Constants\Annotation\Message;
use Hyperf\Constants\EnumConstantsTrait;

#[Constants]
enum VirtualType: int
{
    use EnumConstantsTrait;

    #[Message('次元形象')]
    case FIGURE = 1;

    #[Message('勋章')]
    case MEDAL = 2;
}