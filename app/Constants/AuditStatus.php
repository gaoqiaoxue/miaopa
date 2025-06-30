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

    // 0待审核 1已发布 2已拒绝
    #[Message('待审核')]
    case PENDING = 0;

    #[Message('已发布')]
    case PUBLISHED = 1;

    #[Message('已拒绝')]
    case REJECTED = 2;

    public static function getMaps(): array
    {
        return [
            self::PENDING->value => self::PENDING->getMessage(),
            self::PUBLISHED->value => self::PUBLISHED->getMessage(),
            self::REJECTED->value => self::REJECTED->getMessage(),
        ];
    }

}
