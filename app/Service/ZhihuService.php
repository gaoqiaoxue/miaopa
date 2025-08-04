<?php

namespace App\Service;

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
        foreach ($circle_map as $keyword => $circle_id){
            Db::table('xhs_zhihu')
                ->where('source_keyword', $keyword)
                ->update(['circle_id' => $circle_id]);
        }
        return true;
    }
}