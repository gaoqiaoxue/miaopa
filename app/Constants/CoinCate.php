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

    #[Message('发帖')]
    case POST = 3;

    #[Message('评论')]
    case COMMENT = 4;

    #[Message('报名')]
    case ACTIVITY = 5;

    #[Message('停留')]
    case STAY = 6;

    #[Message('兑换')]
    case EXCHANGE = 10;


    public static function getMaps(): array
    {
        return [
            self::SIGN->value => self::SIGN->getMessage(),
            self::CON_SIGN->value => self::CON_SIGN->getMessage(),
            self::POST->value => self::POST->getMessage(),
            self::COMMENT->value => self::COMMENT->getMessage(),
            self::ACTIVITY->value => self::ACTIVITY->getMessage(),
            self::STAY->value => self::STAY->getMessage(),
            self::EXCHANGE->value => self::EXCHANGE->getMessage(),

        ];
    }

    public static function getKeys():array
    {
        return [
            self::SIGN->value,
            self::CON_SIGN->value,
            self::POST->value,
            self::COMMENT->value,
            self::ACTIVITY->value,
            self::STAY->value,
            self::EXCHANGE->value,
        ];
    }

}
