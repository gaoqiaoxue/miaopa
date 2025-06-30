<?php

namespace App\Constants;

use Hyperf\Constants\Annotation\Constants;
use Hyperf\Constants\Annotation\Message;
use Hyperf\Constants\EnumConstantsTrait;

/**
 * 角色类型
 */
#[Constants]
enum RoleType: int
{
    use EnumConstantsTrait;

    #[Message('动漫角色')]
    case CARTOON = 1;

    #[Message('游戏角色')]
    case GAME = 2;
    
    public static function getMaps(): array
    {
        return [
            self::CARTOON->value => self::CARTOON->getMessage(),
            self::GAME->value => self::GAME->getMessage(),
        ];
    }

    public static function getKeys():array
    {
        return [
            self::CARTOON->value,
            self::GAME->value,
        ];
    }
}
