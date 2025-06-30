<?php

namespace App\Constants;

use Hyperf\Constants\Annotation\Constants;
use Hyperf\Constants\Annotation\Message;
use Hyperf\Constants\EnumConstantsTrait;

/**
 * 活动报名状态
 */
#[Constants]
enum ActivityUserStatus: int
{
    use EnumConstantsTrait;

    #[Message('待支付')]
    case WAIT_PAY = 0;

    #[Message('已报名')]
    case JOINED = 1;

    #[Message('已取消')]
    case CANCEL = 2;

    public static function getMaps(): array
    {
        return [
            self::WAIT_PAY->value => self::WAIT_PAY->getMessage(),
            self::JOINED->value => self::JOINED->getMessage(),
            self::CANCEL->value => self::CANCEL->getMessage(),
        ];
    }

    public static function getKeys():array
    {
        return [
            self::WAIT_PAY->value,
            self::JOINED->value,
            self::CANCEL->value,
        ];
    }

}
