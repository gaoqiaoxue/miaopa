<?php

namespace App\Service;

use App\Constants\AuditStatus;
use Hyperf\DbConnection\Db;

class ZDouYinService
{
    public function saveJsonToDb($filePath)
    {
        $jsonData = file_get_contents($filePath);
        $data = json_decode($jsonData, true);
        foreach ($data as $item) {
            $content_id = $item['aweme_id'];
            $has = Db::table('xhs_douyin')->where('aweme_id', $content_id)->count();
            if ($has > 0) {
                continue;
            }
            $item['liked_count'] == 'None' && $item['liked_count'] = 0;

            $item['collected_count'] == 'None' && $item['collected_count'] = 0;
            $item['comment_count'] == 'None' && $item['comment_count'] = 0;
            $item['share_count'] == 'None' && $item['share_count'] = 0;
            Db::table('xhs_douyin')->insert($item);
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
        foreach ($circle_map as $keyword => $circle_id){
            Db::table('xhs_douyin')
                ->where('source_keyword', $keyword)
                ->where('circle_id', '=', 0)
                ->update(['circle_id' => $circle_id]);
        }
        return true;
    }

    public function transPost()
    {
        $info = Db::table('xhs_douyin')
            ->where('circle_id', '>',  0)
            ->where('get_media', '=', 0)
            ->first();
        if(empty($info)){
            return 0;
        }
        $path = 'xhs/dou/videos/'.$info->aweme_id.'/video.mp4';
        $url = generateFileUrl($path);
        if(!$this->checkMedia($url)){
            Db::table('xhs_douyin')
                ->where('aweme_id', $info->aweme_id)
                ->update(['get_media' => 2]);
            return 1;
        }
        $to_user_id = rand(1057,1437);
        $post_id = Db::table('post')->insertGetId([
            'source' => 'admin',
            'title' => $info->title,
            'post_type' => 1,
            'circle_id' => $info->circle_id,
            'user_id' => $to_user_id,
            'content' => '',
            'media_type' => 2,
            'media' => $path,
            'audit_status' => AuditStatus::PASSED->value,
            'create_time' => date('Y-m-d H:i:s',$info->create_time),
            'update_time' => date('Y-m-d H:i:s',$info->create_time),
        ]);
        Db::table('xhs_douyin')
            ->where('aweme_id', $info->aweme_id)
            ->update(['get_media' => 1, 'to_user_id' => $to_user_id, 'post_id' => $post_id]);
        return 1;
    }

    protected function checkMedia($ossUrl)
    {
        $client = \Hyperf\Context\ApplicationContext::getContainer()
            ->get(\Hyperf\Guzzle\ClientFactory::class)
            ->create();
        try {
            $response = $client->head($ossUrl);
            return $response->getStatusCode() === 200;
        } catch (\Exception $e) {
            return false;
        }
    }

}