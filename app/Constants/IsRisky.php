<?php

namespace App\Constants;

use Hyperf\Constants\Annotation\Constants;
use Hyperf\Constants\Annotation\Message;
use Hyperf\Constants\EnumConstantsTrait;

/**
 * 启用禁用状态
 */
#[Constants]
enum IsRisky: int
{
    use EnumConstantsTrait;

//    0未校验 1校验中 2无风险 3有风险
    #[Message('未校验')]
    case UNKNOWN = 0;

    #[Message('校验中')]
    case DEPEND = 1;

    #[Message('无风险')]
    case SAFE = 2;

    #[Message('有风险')]
    case RISKY = 3;

    public static function getMaps(): array
    {
        return [
            self::UNKNOWN->value => '未校验',
            self::DEPEND->value => '校验中',
            self::SAFE->value => '无风险',
            self::RISKY->value => '有风险',
        ];
    }

    public static function getKeys():array
    {
        return [
            self::UNKNOWN->value,
            self::DEPEND->value,
            self::SAFE->value,
            self::RISKY->value,
        ];
    }

}
