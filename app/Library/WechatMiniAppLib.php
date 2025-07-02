<?php

namespace App\Library;

use App\Exception\ParametersException;
use EasyWeChat\MiniApp\Application;
use Hyperf\Config\Annotation\Value;

class WechatMiniAppLib
{
    protected $app;

    #[Value('wechat.miniapp')]
    protected $config;

    public function __construct()
    {
        $this->app = new Application($this->config);
    }

    // 小程序登录
    public function jscode2session($code)
    {
        try {
            return $this->app->getUtils()->codeToSession($code);
        } catch (\Throwable $e) {
            throw new ParametersException($e->getMessage());
        }
    }

    // 获取手机号
    public function getPhoneNumber($code)
    {
        try {
            return $this->app->getClient()->postJson('wxa/business/getuserphonenumber', [
                'code' => $code
            ]);
        } catch (\Throwable $e) {
            throw new ParametersException($e->getMessage());
        }
    }

    // 获取不限制的小程序码
    public function getwxacodeunlimit($scene, $page = 'pages/index/index', $save_url = '/tmp/wxacode-123.png')
    {
        try {
            $response = $this->app->getClient()->postJson('/wxa/getwxacodeunlimit', [
                'scene' => $scene,
                'page' => $page,
                'width' => 430,
                'check_path' => false,
            ]);
            return $response->saveAs($save_url);
        } catch (\Throwable $e) {
            throw new ParametersException($e->getMessage());
        }
    }
}