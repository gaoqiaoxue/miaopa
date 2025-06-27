<?php

declare(strict_types=1);

namespace App\Constants;

use Hyperf\Constants\Annotation\Constants;
use Hyperf\Constants\Annotation\Message;
use Hyperf\Constants\EnumConstantsTrait;

#[Constants]
enum ErrorCode:int
{
    use EnumConstantsTrait;
    #[Message("系统错误")]
    case SERVER_ERROR = 500;

    #[Message("请先登录")]
    case UNAUTHORIZED = 401;

    public static function getMaps(): array
    {
        return [
            self::SERVER_ERROR->value => self::SERVER_ERROR->name,
            self::UNAUTHORIZED->value => self::UNAUTHORIZED->name,
        ];
    }

}
