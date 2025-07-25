<?php

namespace App\Constants;

use Hyperf\Constants\Annotation\Constants;
use Hyperf\Constants\Annotation\Message;
use Hyperf\Constants\EnumConstantsTrait;

/**
 * 活动类型
 */
#[Constants]
enum ActivityType: int
{
    use EnumConstantsTrait;

    #[Message('漫展')]
    case COMIC_CON = 1;

    public static function getMaps(): array
    {
        return [
            self::COMIC_CON->value => self::COMIC_CON->getMessage(),
        ];
    }

    public static function getKeys():array
    {
        return [
            self::COMIC_CON->value,
        ];
    }

    public static function getColor($value)
    {
        $list = [
            self::COMIC_CON->value => '#B2A9FF', // #DF9CE3   #E5BE75  #72B8FF
        ];
        return $list[$value] ?? '';
    }

}
