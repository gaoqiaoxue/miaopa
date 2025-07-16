<?php

namespace App\Constants;

use Hyperf\Constants\Annotation\Constants;
use Hyperf\Constants\Annotation\Message;
use Hyperf\Constants\EnumConstantsTrait;

/**
 * 金币出入账目类型
 */
#[Constants]
enum PrestigeCate: int
{
    use EnumConstantsTrait;

    #[Message('发动态')]
    case DYNAMIC = 1;

    #[Message('发问答')]
    case QA = 2;

    #[Message('被回复')]
    case BE_COMMENTED = 3;

    #[Message('获赞')]
    case BE_LIKED = 4;

    #[Message('举报成功')]
    case REPORT = 5;

    #[Message('粉丝')]
    case FANS = 6;

    #[Message('点赞')]
    case LIKE = 7;

    #[Message('分享')]
    case SHARE = 8;

    #[Message('回复')]
    case COMMENT = 9;

    #[Message('关注')]
    case FOLLOW = 10;

    public static function getMaps(): array
    {
        return [
            self::DYNAMIC->value => self::DYNAMIC->getMessage(),
            self::QA->value => self::QA->getMessage(),
            self::BE_COMMENTED->value => self::BE_COMMENTED->getMessage(),
            self::BE_LIKED->value => self::BE_LIKED->getMessage(),
            self::REPORT->value => self::REPORT->getMessage(),
            self::FANS->value => self::FANS->getMessage(),
            self::LIKE->value => self::LIKE->getMessage(),
            self::SHARE->value => self::SHARE->getMessage(),
            self::COMMENT->value => self::COMMENT->getMessage(),
            self::FOLLOW->value => self::FOLLOW->getMessage(),
        ];
    }

    public static function getKeys():array
    {
        return [
            self::DYNAMIC->value,
            self::QA->value,
            self::BE_COMMENTED->value,
            self::BE_LIKED->value,
            self::REPORT->value,
            self::FANS->value,
            self::LIKE->value,
            self::SHARE->value,
            self::COMMENT->value,
            self::FOLLOW->value,
        ];
    }

}
