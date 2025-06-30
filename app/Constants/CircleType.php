<?php

namespace App\Constants;

use Hyperf\Constants\Annotation\Constants;
use Hyperf\Constants\Annotation\Message;
use Hyperf\Constants\EnumConstantsTrait;

/**
 * 圈子类型
 */
#[Constants]
enum CircleType: int
{
    use EnumConstantsTrait;

    #[Message('圈子')]
    case CIRCLE = 1;

    #[Message('动漫IP')]
    case CARTOON = 2;

    #[Message('游戏IP')]
    case GAME = 3;

    public static function getMaps(): array
    {
        return [
            self::CIRCLE->value => self::CIRCLE->getMessage(),
            self::CARTOON->value => self::CARTOON->getMessage(),
            self::GAME->value => self::GAME->getMessage(),
        ];
    }

    public static function getKeys():array
    {
        return [
            self::CIRCLE->value,
            self::CARTOON->value,
            self::GAME->value,
        ];
    }
}
