<?php

namespace App\Constants;

use Hyperf\Constants\Annotation\Constants;
use Hyperf\Constants\Annotation\Message;
use Hyperf\Constants\EnumConstantsTrait;

/**
 * 举报类型
 */
#[Constants]
enum ReportType: int
{
    use EnumConstantsTrait;

    #[Message('帖子')]
    case POST = 1;

    #[Message('评论')]
    case COMMENT = 2;

    public static function getMaps(): array
    {
        return [
            self::POST->value => self::POST->name,
            self::COMMENT->value => self::COMMENT->name,
        ];
    }

    public static function getKeys():array
    {
        return [
            self::POST->value,
            self::COMMENT->value,
        ];
    }
}