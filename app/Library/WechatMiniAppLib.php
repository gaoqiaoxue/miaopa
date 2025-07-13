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

    // 文本内容安全监测
    public function msgSecCheck($content,$openid)
    {
        try {
            $res = $this->app->getClient()->postJson('wxa/msg_sec_check', [
                'content' => $content,
                'openid' => $openid,
                'version' => '2',
                'scene' => '2' // 1 资料；2 评论；3 论坛；4 社交日志
            ]);
            logGet('msgSecCheck','wxmini')->debug(json_encode([
                'content' => $content,
                'openid' => $openid,
                'res' => $res
            ]));
            if(isset($res['result']['suggest']) && $res['result']['suggest'] == 'pass'){
                return false; //无风险
            }
            return true; // 有风险
        } catch (\Throwable $e) {
            logGet('msgSecCheck','wxmini')->debug($e->getMessage());
            return false;
        }
    }

    // 多媒体内容安全识别
    public function mediaCheckAsync($mediaUrl,$openid,$mediaType = 2)
    {
        try {
            $result = $this->app->getClient()->postJson('wxa/media_check_async', [
                'media_url' => $mediaUrl,
                'media_type' => $mediaType, // 1:音频;2:图片
                'version' => '2',
                'scene' => '1', // 1 资料；2 评论；3 论坛；4 社交日志
                'openid' => $openid
            ]);
            if($result['errcode'] == 0) {
                return $result['trace_id'];
            }else{
                logGet('mediaCheck','wxmini')->debug(json_encode([
                    'media_url' => $mediaUrl,
                    'media_type' => $mediaType, // 1:音频;2:图片
                    'res' => $result
                ]));
                throw new ParametersException($result['errmsg'] ?? '内容安全校验失败');
            }
        } catch (\Throwable $e) {
            logGet('mediaCheck','wxmini')->debug($e->getMessage());
            throw new ParametersException($e->getMessage());
        }
    }
}