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
    'default' => [
        'handlers' => [
            [
                'class' => Monolog\Handler\RotatingFileHandler::class,
                'constructor' => [
                    'filename' =>  '/www/wwwroot/miaopaci/runtime/logs/hyperf.log',
                    'level' => Monolog\Logger::INFO,
                ],
                'formatter' => [
                    'class' => Monolog\Formatter\LineFormatter::class,
                    'constructor' => [
                        'format' => null,
                        'dateFormat' => 'Y-m-d H:i:s',
                        'allowInlineLineBreaks' => true,
                    ],
                ],
            ],
            [
                'class' => Monolog\Handler\RotatingFileHandler::class,
                'constructor' => [
                    'filename' =>  '/www/wwwroot/miaopaci/runtime/logs/hyperf-error.log',
                    'level' => Monolog\Logger::ERROR,
                ],
                'formatter' => [
                    'class' => Monolog\Formatter\LineFormatter::class,
                    'constructor' => [
                        'format' => null,
                        'dateFormat' => 'Y-m-d H:i:s',
                        'allowInlineLineBreaks' => true,
                    ],
                ],
            ],
        ],
    ],
    'wxmini' => [
        'handler' => [
            'class' => Monolog\Handler\RotatingFileHandler::class,
            'constructor' => [
                'filename' => '/www/wwwroot/miaopaci/runtime/logs/wxmini.log',
                'level' => \Monolog\Logger::DEBUG,
            ],
        ],
        'formatter' => [
            'class' => Monolog\Formatter\LineFormatter::class,
            'constructor' => [
                'format' => null,
                'dateFormat' => null,
                'allowInlineLineBreaks' => true,
            ],
        ],
    ],
    'task' => [
        'handler' => [
            'class' => Monolog\Handler\RotatingFileHandler::class,
            'constructor' => [
                'filename' => '/www/wwwroot/miaopaci/runtime/logs/task.log',
                'level' => \Monolog\Logger::DEBUG,
            ],
        ],
        'formatter' => [
            'class' => Monolog\Formatter\LineFormatter::class,
            'constructor' => [
                'format' => null,
                'dateFormat' => null,
                'allowInlineLineBreaks' => true,
            ],
        ],
    ],
];
