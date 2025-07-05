<?php

namespace App\Constants;

use Hyperf\Constants\Annotation\Constants;
use Hyperf\Constants\Annotation\Message;
use Hyperf\Constants\EnumConstantsTrait;

/**
 * 举报理由
 */
#[Constants]
enum ReportReason: int
{
    use EnumConstantsTrait;

    #[Message('垃圾内容')]
    case R1 = 1;

    #[Message('违法违规')]
    case R2 = 2;

    #[Message('色情低俗')]
    case R3 = 3;

    #[Message('欺诈信息')]
    case R4 = 4;

    #[Message('抄袭侵权')]
    case R5 = 5;

    #[Message('人身攻击')]
    case R6 = 6;

    #[Message('虚假信息')]
    case R7 = 7;

    #[Message('其他')]
    case R99 = 99;



    public static function getMaps(): array
    {
        return [
            self::R1->value => self::R1->getMessage(),
            self::R2->value => self::R2->getMessage(),
            self::R3->value => self::R3->getMessage(),
            self::R4->value => self::R4->getMessage(),
            self::R5->value => self::R5->getMessage(),
            self::R6->value => self::R6->getMessage(),
            self::R7->value => self::R7->getMessage(),
            self::R99->value => self::R99->getMessage(),
        ];
    }

    public static function getKeys():array
    {
        return [
            self::R1->value,
            self::R2->value,
            self::R3->value,
            self::R4->value,
            self::R5->value,
            self::R6->value,
            self::R7->value,
            self::R99->value,
        ];
    }

}
