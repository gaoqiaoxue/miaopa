<?php

namespace App\Constants;

use Hyperf\Constants\Annotation\Constants;
use Hyperf\Constants\Annotation\Message;
use Hyperf\Constants\EnumConstantsTrait;

/**
 * 圈子关联类型
 */
#[Constants]
enum CircleRelationType: string
{
    use EnumConstantsTrait;

    #[Message('圈子')]
    case CIRCLE = 'circle';

    #[Message('角色')]
    case ROLE = 'role';

    public static function getMaps(): array
    {
        return [
            self::CIRCLE->value => self::CIRCLE->name,
            self::ROLE->value => self::ROLE->name,
        ];
    }

}
