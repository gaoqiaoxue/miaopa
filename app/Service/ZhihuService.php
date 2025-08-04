<?php

namespace App\Service;

use App\Constants\AuditStatus;
use App\Constants\PostType;
use Hyperf\DbConnection\Db;

class ZhihuService
{
    public function saveJsonToDb($filePath)
    {
        $jsonData = file_get_contents($filePath);
        $data = json_decode($jsonData, true);
        foreach ($data as $item) {
            $content_id = $item['content_id'];
            $has = Db::table('xhs_zhihu')->where('content_id', $content_id)->count();
            if ($has > 0) {
                continue;
            }
            Db::table('xhs_zhihu')->insert($item);
        }
    }

    public function transCircle()
    {
        $circle_map = [
            // cosplay 圈子 (id:82)
            'cosplay' => 82,
            'coser' => 82,
            '二次元' => 82,

            // 潮玩 圈子 (id:84)
            '潮玩' => 84,
            '泡泡玛特' => 84,
            'pop' => 84,
            'LABUBU' => 84,
            'POP(泡泡玛特)' => 84,
            'POP(泡泡玛特)，LABUBU' => 84,

            // 游戏 圈子 (id:86)
            '游戏' => 86,
            '原神' => 86,
            '崩坏' => 86,
            '排球少年' => 86,
            '鬼灭之刃' => 86,
            '咒术回战' => 86,

            // 谷子 圈子 (id:83 - 谷图)
            '谷子' => 83,
            '吧唧' => 83,

            // 手办 圈子 (id:85)
            '手办' => 85
        ];
        foreach ($circle_map as $keyword => $circle_id) {
            Db::table('xhs_zhihu')
                ->where('source_keyword', $keyword)
                ->update(['circle_id' => $circle_id]);
        }
        return true;
    }

    public function transPost()
    {
        var_dump('234234234');
        $info = Db::table('xhs_zhihu')
            ->where('circle_id', '>', 0)
            ->whereNull('post_id')
            ->first();
        var_dump($info);
        if (empty($info)) {
            return 0;
        }
        $to_user_id = rand(1057, 1437);
        $answer_user_id = 0;
        $answer_id = 0;
        if ($info->content_type == 'answer') {
            $post_id = Db::table('post')->insertGetId([
                'source' => 'admin',
                'title' => $info->title,
                'post_type' => PostType::QA->value,
                'circle_id' => $info->circle_id,
                'user_id' => $to_user_id,
                'content' => '',
                'media_type' => 1,
                'media' => '',
                'audit_status' => AuditStatus::PASSED->value,
                'create_time' => date('Y-m-d H:i:s', $info->created_time),
                'update_time' => date('Y-m-d H:i:s', $info->updated_time),
            ]);
            $answer_user_id = rand(1057, 1437);
            $answer_id = Db::table('comment')->insertGetId([
                'source' => 'admin',
                'post_id' => $post_id,
                'post_type' => PostType::QA->value,
                'user_id' => $answer_user_id,
                'content' => $info->content_text,
                'audit_status' => AuditStatus::PASSED->value,
                'create_time' => date('Y-m-d H:i:s', $info->created_time),
                'update_time' => date('Y-m-d H:i:s', $info->updated_time),
            ]);
        } elseif ($info->content_type == 'article') {
            $post_id = Db::table('post')->insertGetId([
                'source' => 'admin',
                'title' => $info->title,
                'post_type' => PostType::DYNAMIC->value,
                'circle_id' => $info->circle_id,
                'user_id' => $to_user_id,
                'content' => $info->content_text,
                'media_type' => 1,
                'media' => '',
                'audit_status' => AuditStatus::PASSED->value,
                'create_time' => date('Y-m-d H:i:s', $info->created_time),
                'update_time' => date('Y-m-d H:i:s', $info->updated_time),
            ]);
        } else {
            $post_id = -1;
        }
        Db::table('xhs_zhihu')
            ->where('content_id', $info->content_id)
            ->update([
                'to_user_id' => $to_user_id,
                'post_id' => $post_id,
                'answer_id' => $answer_id,
                'answer_user_id' => $answer_user_id
            ]);
        return 1;
    }

}