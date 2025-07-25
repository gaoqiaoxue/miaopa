<?php

namespace App\Constants;

use Hyperf\Constants\Annotation\Constants;
use Hyperf\Constants\Annotation\Message;
use Hyperf\Constants\EnumConstantsTrait;

#[Constants]
enum VirtualType: int
{
    use EnumConstantsTrait;

    #[Message('次元形象')]
    case FIGURE = 1;

    #[Message('勋章')]
    case MEDAL = 2;

    public static function getMaps(): array
    {
        return [
            self::FIGURE->value => self::FIGURE->getMessage(),
            self::MEDAL->value => self::MEDAL->getMessage(),
        ];
    }

    public static function getKeys():array
    {
        return [
            self::FIGURE->value,
            self::MEDAL->value,
        ];
    }
}