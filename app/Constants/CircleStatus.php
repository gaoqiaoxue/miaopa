<?php

namespace App\Constants;

use Hyperf\Constants\Annotation\Constants;
use Hyperf\Constants\Annotation\Message;
use Hyperf\Constants\EnumConstantsTrait;

#[Constants]
enum CircleStatus: int
{
    use EnumConstantsTrait;

    #[Message('关闭')]
    case CLOSE = 0;

    #[Message('正常')]
    case OPEN = 1;

    public static function getMaps()
    {
        return [
            self::CLOSE->value => self::CLOSE->name,
            self::OPEN->value => self::OPEN->name,
        ];
    }
}