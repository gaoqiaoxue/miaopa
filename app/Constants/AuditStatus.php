<?php

namespace App\Constants;

use Hyperf\Constants\Annotation\Constants;
use Hyperf\Constants\Annotation\Message;
use Hyperf\Constants\EnumConstantsTrait;

/**
 * 审核状态
 */
#[Constants]
enum AuditStatus: int
{
    use EnumConstantsTrait;

    #[Message('待审核')]
    case PENDING = 0;

    #[Message('已通过')]
    case PASSED = 1;

    #[Message('已拒绝')]
    case REJECTED = 2;

    public static function getMaps(): array
    {
        return [
            self::PENDING->value => self::PENDING->getMessage(),
            self::PASSED->value => self::PASSED->getMessage(),
            self::REJECTED->value => self::REJECTED->getMessage(),
        ];
    }

    public static function getKeys():array
    {
        return [
            self::PENDING->value,
            self::PASSED->value,
            self::REJECTED->value,
        ];
    }
}
