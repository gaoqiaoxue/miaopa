<?php

namespace App\Service;

use App\Constants\IsRisky;
use App\Library\WechatMiniAppLib;
use Hyperf\DbConnection\Db;
use Hyperf\Di\Annotation\Inject;

class MediaAuditService
{

    #[Inject]
    protected WechatMiniAppLib $mini_lib;

    public function addMediaAudit(int $user_id, string $openid, string $media_url, string $type, int $refer_id): int
    {
        $att = Db::table('sys_upload')->where('url', $media_url)->first();
        if (!empty($att) && in_array($att->is_risky, [IsRisky::SAFE->value, IsRisky::RISKY->value])) {
            return $att->is_risky;
        }
        $trace_id = $this->mini_lib->mediaCheckAsync(generateFileUrl($media_url), $openid, $type);
        Db::table('sys_media_audit')->insert([
            'user_id' => $user_id,
            'url' => $media_url,
            'type' => $type,
            'content_id' => $refer_id,
            'trace_id' => $trace_id,
            'is_risky' => IsRisky::DEPEND->value,
            'create_time' => date('Y-m-d H:i:s'),
            'update_time' => date('Y-m-d H:i:s'),
        ]);
        if (!empty($att)) {
            Db::table('sys_upload')->where('upload_id', $att->upload_id)->update([
                'is_risky' => IsRisky::DEPEND->value,
                'trace_id' => $trace_id,
            ]);
        }
        return IsRisky::DEPEND->value;
    }

    public function updateMediaAudit(mixed $trace_id, int $is_risky): bool
    {
        $info = Db::table('sys_media_audit')->where('trace_id', $trace_id)->first();
        if (empty($info)) {
            return true;
        }
        Db::table('sys_media_audit')->where('trace_id', $trace_id)->update([
            'is_risky' => $is_risky,
            'update_time' => date('Y-m-d H:i:s'),
        ]);
        Db::table('sys_upload')->where('trace_id', $trace_id)->update([
            'is_risky' => $is_risky
        ]);
        if ($is_risky == IsRisky::SAFE->value && ($info->type == 'avatar' || $info->type == 'bg')) {
            Db::table('user')->where('id', $info->user_id)->update([
                $info->type => $info->url,
                'update_time' => date('Y-m-d H:i:s'),
            ]);
        }
        return true;
    }

}