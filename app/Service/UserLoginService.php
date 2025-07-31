<?php

namespace App\Service;

use App\Exception\LogicException;
use App\Library\Contract\AuthTokenInterface;
use App\Library\WechatMiniAppLib;
use Hyperf\DbConnection\Db;
use Hyperf\Di\Annotation\Inject;

class UserLoginService
{
    #[Inject]
    protected AuthTokenInterface $authToken;

    #[Inject]
    protected WechatMiniAppLib $mini_lib;

    public function login(array $data): array
    {
        $user = Db::table('user')->where(['username' => $data['username']])->first();
        if (!$user) {
            throw new LogicException('账号不存在');
        }
//        if (!checkPassword($data['password'], $user->password)) {
//            throw new LogicException('账号或密码错误');
//        }
        return $this->returnLoginData($user);
    }

    public function wechatMiniAuth(string $code): array
    {
        $info = $this->mini_lib->jscode2session($code);
//        $info = [
//            'openid' => 'jhuwienjxkhfoakshd' // TODO
//        ];
        if (empty($info['openid'])) {
            throw new LogicException('微信授权失败');
        }
        $core = Db::table('user_core')
            ->where('source', '=', 'wechatmini')
            ->where('openid', '=', $info['openid'])
            ->first();
        if (!$core) {
            $core = [
                'source' => 'wechatmini',
                'openid' => $info['openid'],
                'unionid' => $info['unionid'] ?? '',
                'user_id' => 0,
                'create_time' => date('Y-m-d H:i:s'),
                'update_time' => date('Y-m-d H:i:s'),
            ];
            $core_id = Db::table('user_core')->insertGetId($core);
            $user_id = 0;
        } else {
            $user_id = $core->user_id;
            $core_id = $core->id;
        }
        if (!empty($user_id)) {
            $user = Db::table('user')->where('id', '=', $user_id)->first();
            $result = $this->returnLoginData($user);
            $result['core_id'] = $core_id;
            return $result;
        }
        return ['core_id' => $core_id];
    }

    protected function returnLoginData(object $user): array
    {
        $user_data = [
            'user_id' => $user->id,
            'username' => $user->username,
            'nickname' => $user->nickname,
            'avatar' => $user->avatar,
            'avatar_url' => getAvatar($user->avatar),
        ];
        $token = $this->authToken->createToken($user_data);
        Db::table('user')->where(['id' => $user->id])->update([
            'last_login_time' => date('Y-m-d H:i:s'),
        ]);
        $token['token'] = 'Bearer '.$token['token'];
        return [
            'user' => $user,
            'token' => $token,
        ];
    }

    public function wechatMiniGetPhoneNumber(string $code, $core_id): array
    {
//        $mobile = '13500135000'; // TODO
        $info = $this->mini_lib->getPhoneNumber($code);
        if (empty($info['phone_info']['phoneNumber'])) {
            throw new LogicException('手机号获取失败');
        }
        $mobile = $info['phone_info']['phoneNumber'];
        return $this->bindMobile('wechatmini', $mobile, $core_id);
    }

    protected function bindMobile(string $source, string $mobile, int $core_id): array
    {

        $has_user = Db::table('user')
            ->where('mobile', '=', $mobile)
            ->first();
        if ($has_user) {
            $user = $has_user;
            $has_core = Db::table('user_core')
                ->where('user_id', '=', $user->id)
                ->where('source', '=', $source)
                ->first();
            if (!empty($has_core) && $has_core->id != $core_id) {
                throw new LogicException('手机号已绑定其他账号');
            }
            return $this->returnLoginData($user);
        } else {
            Db::beginTransaction();
            try {
                $user = [
                    'name' => '',
                    'username' => $mobile,
                    'nickname' => $this->getNickname(),
                    'mobile' => $mobile,
                    'avatar' => '',
                    'create_time' => date('Y-m-d H:i:s'),
                    'update_time' => date('Y-m-d H:i:s'),
                ];
                $user_id = Db::table('user')->insertGetId($user);
                Db::table('user_credit')->insert(['user_id' => $user_id]);
                Db::table('user_core')->where('id', '=', $core_id)->update(['user_id' => $user_id]);
                Db::commit();
            }catch (\Throwable $ex){
                Db::rollBack();
                throw new LogicException($ex->getMessage());
            }
            $user['id'] = $user_id;
            return $this->returnLoginData((object)$user);
        }
    }

    private function getNickname()
    {
        $nickname = '';
        for ($i = 0; $i < 6; $i++) {
            $nickname .= chr(mt_rand(97, 122));
        }
        return $nickname;
    }

    public function refreshToken(): array
    {
        return $this->authToken->refreshToken();
    }

    public function logout(): bool
    {
        if (!$this->authToken->logout()) {
            throw new LogicException('退出失败');
        }
        return true;
    }
}