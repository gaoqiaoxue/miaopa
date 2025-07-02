<?php

declare(strict_types=1);

return [
    'official' => [
        'app_id'  => 'my_wechat',         // AppID
        'secret'  => '',     // AppSecret
        'token'   => '',          // Token
        'aes_key' => '',                    // EncodingAESKey，兼容与安全模式下请一定要填写！！！

        /**
         * 是否使用 Stable Access Token
         * 默认 false
         * https://developers.weixin.qq.com/doc/offiaccount/Basic_Information/getStableAccessToken.html
         * true 使用 false 不使用
         */
        'use_stable_access_token' => false,

        /**
         * OAuth 配置
         *
         * scopes：公众平台（snsapi_userinfo / snsapi_base），开放平台：snsapi_login
         * redirect_url：OAuth授权完成后的回调页地址
         */
        'oauth' => [
            'scopes'   => ['snsapi_userinfo'],
            'redirect_url' => '',
        ],

        /**
         * 接口请求相关配置，超时时间等，具体可用参数请参考：
         * https://github.com/symfony/symfony/blob/5.3/src/Symfony/Contracts/HttpClient/HttpClientInterface.php
         */
        'http' => [
            'timeout' => 5.0,
            // 'base_uri' => 'https://api.weixin.qq.com/', // 如果你在国外想要覆盖默认的 url 的时候才使用，根据不同的模块配置不同的 uri

            'retry' => true, // 使用默认重试配置
            //  'retry' => [
            //      // 仅以下状态码重试
            //      'status_codes' => [429, 500]
            //       // 最大重试次数
            //      'max_retries' => 3,
            //      // 请求间隔 (毫秒)
            //      'delay' => 1000,
            //      // 如果设置，每次重试的等待时间都会增加这个系数
            //      // (例如. 首次:1000ms; 第二次: 3 * 1000ms; etc.)
            //      'multiplier' => 3
            //  ],
        ],
    ],
    'miniapp' => [
        'app_id' => 'wx3cf0f39249eb0exx',
        'secret' => 'f1c242f4f28f735d4687abb469072axx',
        'token' => '',
        'aes_key' => '',

        /**
         * 接口请求相关配置，超时时间等，具体可用参数请参考：
         * https://github.com/symfony/symfony/blob/5.3/src/Symfony/Contracts/HttpClient/HttpClientInterface.php
         */
        'http' => [
            'throw'  => true, // 状态码非 200、300 时是否抛出异常，默认为开启
            'timeout' => 5.0,
            // 'base_uri' => 'https://api.weixin.qq.com/', // 如果你在国外想要覆盖默认的 url 的时候才使用，根据不同的模块配置不同的 uri

            'retry' => true, // 使用默认重试配置
            //  'retry' => [
            //      // 仅以下状态码重试
            //      'status_codes' => [429, 500]
            //       // 最大重试次数
            //      'max_retries' => 3,
            //      // 请求间隔 (毫秒)
            //      'delay' => 1000,
            //      // 如果设置，每次重试的等待时间都会增加这个系数
            //      // (例如. 首次:1000ms; 第二次: 3 * 1000ms; etc.)
            //      'multiplier' => 3
            //  ],
        ],
    ]
];
