<?php

namespace App\Constants;

use Hyperf\Constants\Annotation\Constants;
use Hyperf\Constants\Annotation\Message;
use Hyperf\Constants\EnumConstantsTrait;

/**
 * 启用禁用状态
 */
#[Constants]
enum CabinetType: int
{
    use EnumConstantsTrait;

    // 1卡柜 2娃柜 3谷柜 99其他
    #[Message('卡柜')]
    case CARD = 1;

    #[Message('娃柜')]
    case DOLL = 2;

    #[Message('谷柜')]
    case GU = 3;

    #[Message('其他')]
    case OTHER = 99;

    public static function getMaps(): array
    {
        return [
            self::CARD->value => self::CARD->getMessage(),
            self::DOLL->value => self::DOLL->getMessage(),
            self::GU->value => self::GU->getMessage(),
            self::OTHER->value => self::OTHER->getMessage(),
        ];
    }

    public static function getKeys():array
    {
        return [
            self::CARD->value,
            self::DOLL->value,
            self::GU->value,
            self::OTHER->value,
        ];
    }

}