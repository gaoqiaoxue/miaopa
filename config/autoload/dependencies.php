<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */
return [
    // Jwt Auth Token
    \App\Library\Contract\AuthTokenInterface::class => \App\Library\JwtAuthTokenLib::class,
    \App\Library\Contract\MapWebInterface::class => \App\Library\Map\TmapWebLib::class,
];
