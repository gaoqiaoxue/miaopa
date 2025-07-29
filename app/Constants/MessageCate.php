<?php

namespace App\Constants;

use Hyperf\Constants\Annotation\Constants;
use Hyperf\Constants\Annotation\Message;
use Hyperf\Constants\EnumConstantsTrait;

/**
 * 用户消息分类
 */
#[Constants]
enum MessageCate: int
{
    use EnumConstantsTrait;

    #[Message('审核通过通知')]
    case AUDIT_PASS = 1;

    #[Message('审核驳回通知')]
    case AUDIT_FAIL = 2;

    #[Message('举报审核通过')]
    case REPORT_PASS = 11;

    #[Message('举报审核驳回')]
    case REPORT_FAIL = 12;

    #[Message('角色审核通过')]
    case ROLE_PASS = 13;

    #[Message('角色审核驳回')]
    case ROLE_FAIL = 14;

    #[Message('帖子审核通过')]
    case POST_PASS = 15;

    #[Message('帖子审核驳回')]
    case POST_FAIL = 16;

    #[Message('评论审核通过')]
    case COMMENT_PASS = 17;

    #[Message('评论审核驳回')]
    case COMMENT_FAIL = 18;

    #[Message('系统消息')]
    case SYSTEM = 3;

    #[Message('活动通知')]
    case ACTIVITY = 4;

    #[Message('帖子点赞')]
    case POST_LIKE = 5;

    #[Message('评论点赞')]
    case COMMENT_LIKE = 6;

    #[Message('新增关注')]
    case FANS = 7;

    #[Message('帖子评论')]
    case COMMENT = 8;

    #[Message('回复')]
    case REPLY = 9;

    public static function getMaps(): array
    {
        return [
            self::AUDIT_PASS->value => '审核通过通知',
            self::AUDIT_FAIL->value => '审核驳回通知',
            self::SYSTEM->value => '系统消息',
            self::ACTIVITY->value => '活动通知',
            self::POST_LIKE->value => '帖子点赞',
            self::COMMENT_LIKE->value => '评论点赞',
            self::FANS->value => '新增关注',
            self::COMMENT->value => '帖子评论',
            self::REPLY->value => '回复',
        ];
    }

    public static function getKeys():array
    {
        return [
            self::AUDIT_PASS->value,
            self::AUDIT_FAIL->value,
            self::SYSTEM->value,
            self::ACTIVITY->value,
            self::POST_LIKE->value,
            self::COMMENT_LIKE->value,
            self::FANS->value,
            self::COMMENT->value,
            self::REPLY->value,
        ];
    }

}
