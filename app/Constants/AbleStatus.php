<?php

namespace App\Constants;

use Hyperf\Constants\Annotation\Constants;
use Hyperf\Constants\Annotation\Message;
use Hyperf\Constants\EnumConstantsTrait;

/**
 * 启用禁用状态
 */
#[Constants]
enum AbleStatus: int
{
    use EnumConstantsTrait;

    #[Message('启用')]
    case ENABLE = 1;

    #[Message('禁用')]
    case DISABLE = 0;

    public static function getMaps(): array
    {
        return [
            self::ENABLE->value => self::ENABLE->getMessage(),
            self::DISABLE->value => self::DISABLE->getMessage(),
        ];
    }

    public static function getKeys():array
    {
        return [
            self::ENABLE->value,
            self::DISABLE->value,
        ];
    }

}
