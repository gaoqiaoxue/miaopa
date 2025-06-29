<?php

namespace App\Constants;

use Hyperf\Constants\Annotation\Constants;
use Hyperf\Constants\Annotation\Message;
use Hyperf\Constants\EnumConstantsTrait;

/**
 * 性别
 */
#[Constants]
enum Sex: int
{
    use EnumConstantsTrait;

    #[Message('未知')]
    case UNKNOWN = 0;

    #[Message('男')]
    case MALE = 1;

    #[Message('女')]
    case FEMALE = 2;

    public static function getMaps(): array
    {
        return [
            self::UNKNOWN->value => self::UNKNOWN->getMessage(),
            self::MALE->value => self::MALE->getMessage(),
            self::FEMALE->value => self::FEMALE->getMessage(),
        ];
    }
}
