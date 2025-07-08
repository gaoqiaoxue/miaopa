<?php

namespace App\Constants;

use Hyperf\Constants\Annotation\Constants;
use Hyperf\Constants\Annotation\Message;
use Hyperf\Constants\EnumConstantsTrait;

/**
 * 金币出入账目类型
 */
#[Constants]
enum CoinCate: int
{
    use EnumConstantsTrait;

    #[Message('签到')]
    case SIGN = 1;

    #[Message('连续签到')]
    case CON_SIGN = 2;


    public static function getMaps(): array
    {
        return [
            self::SIGN->value => self::SIGN->getMessage(),
            self::CON_SIGN->value => self::CON_SIGN->getMessage(),
        ];
    }

    public static function getKeys():array
    {
        return [
            self::SIGN->value,
            self::CON_SIGN->value,
        ];
    }

}
