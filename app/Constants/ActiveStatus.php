<?php

namespace App\Constants;

use Hyperf\Constants\Annotation\Constants;
use Hyperf\Constants\Annotation\Message;
use Hyperf\Constants\EnumConstantsTrait;

/**
 * 活动状态
 */
#[Constants]
enum ActiveStatus: int
{
    use EnumConstantsTrait;

    // 1未启动 2进行中 3已结束
    #[Message('未启动')]
    case NOT_START = 1;

    #[Message('进行中')]
    case ONGOING = 2;

    #[Message('已结束')]
    case ENDED = 3;

    public static function getMaps(): array
    {
        return [
            self::NOT_START->value => self::NOT_START->getMessage(),
            self::ONGOING->value => self::ONGOING->getMessage(),
            self::ENDED->value => self::ENDED->getMessage(),
        ];
    }

}
