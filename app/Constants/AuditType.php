<?php

namespace App\Constants;

use Hyperf\Constants\Annotation\Constants;
use Hyperf\Constants\Annotation\Message;
use Hyperf\Constants\EnumConstantsTrait;

/**
 * 启用禁用状态
 */
#[Constants]
enum AuditType: int
{
    use EnumConstantsTrait;

    #[Message('举报处理')]
    case REPORT = 1;

    #[Message('角色审核')]
    case ROLE = 2;

    #[Message('帖子审核')]
    case POST = 3;

    #[Message('评论审核')]
    case COMMENT = 4;

    public static function getMaps(): array
    {
        return [
            self::REPORT->value => self::REPORT->name,
            self::ROLE->value => self::ROLE->name,
            self::POST->value => self::POST->name,
            self::COMMENT->value => self::COMMENT->name,
        ];
    }

    public static function getKeys():array
    {
        return [
            self::REPORT->value,
            self::ROLE->value,
            self::POST->value,
            self::COMMENT->value,
        ];
    }

}
