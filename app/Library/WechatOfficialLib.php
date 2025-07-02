<?php

namespace App\Library;

use App\Exception\ParametersException;
use EasyWeChat\OfficialAccount\Application;
use Hyperf\Config\Annotation\Value;
use function Hyperf\Config\config;

class WechatOfficialLib
{
    protected $app;

    #[Value('wechat.official')]
    protected $config;

    public function __construct()
    {
        $this->app = new Application($this->config);
    }

    public function getRedirectUrl($url = '')
    {
        try {
            $oauth = $this->app->getOauth();
            return $oauth->scopes(['snsapi_userinfo'])->redirect($url);
        }catch (\Throwable $e) {
            throw new ParametersException($e->getMessage());
        }
    }

    public function getUserInfo($code)
    {
        try {
            $oauth = $this->app->getOauth();
            return $oauth->userFromCode($code);
        }catch (\Throwable $e) {
            throw new ParametersException($e->getMessage());
        }
    }

}