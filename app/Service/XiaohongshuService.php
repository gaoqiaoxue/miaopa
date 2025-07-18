<?php


declare(strict_types=1);

namespace App\Service;

use Hyperf\Cache\Annotation\Cacheable;
use Hyperf\DbConnection\Db;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\Logger\LoggerFactory;
use Psr\Log\LoggerInterface;

class XiaohongshuService
{
    /**
     * 搜索笔记
     * @Cacheable(prefix="xhs_search", ttl=3600)
     */
    public function searchNotes(string $keyword, int $page = 1, int $pageSize = 20): array
    {
        $url = 'https://edith.xiaohongshu.com/api/sns/web/v1/search/notes';
        $data = [
            'keyword' => $keyword,
            'page' => $page,
            'page_size' => $pageSize,
            'search_id' => uniqid() . substr(md5((string) time()), 0, 16),
            'sort' => 'general',
            'note_type' => 0,
            'ext_flags' => [],
            'filters' => [
                [
                    'tags' => ['general'],
                    'type' => 'sort_type'
                ],
                [
                    'tags' => ['不限'],
                    'type' => 'filter_note_type'
                ],
                [
                    'tags' => ['不限'],
                    'type' => 'filter_note_time'
                ],
                [
                    'tags' => ['不限'],
                    'type' => 'filter_note_range'
                ],
                [
                    'tags' => ['不限'],
                    'type' => 'filter_pos_distance'
                ]
            ],
            'geo' => '',
            'image_formats' => ['jpg', 'webp', 'avif']
        ];

        $headers = $this->getBaseHeaders();
        $client = new \GuzzleHttp\Client();
        $response = $client->post($url, [
            'headers' => $headers,
            'json' => $data,
            'timeout' => 10,
        ]);

        $result = json_decode($response->getBody()->getContents(), true);
        return $result;
    }

    /**
     * 获取基础请求头
     */
    private function getBaseHeaders(): array
    {
        return [
            'authority' => 'edith.xiaohongshu.com',
            'accept' => 'application/json, text/plain, */*',
            'accept-encoding' => 'gzip, deflate, br, zstd',
            'accept-language' => 'zh-CN,zh;q=0.9',
            'content-type' => 'application/json;charset=UTF-8',
            'origin' => 'https://www.xiaohongshu.com',
            'referer' => 'https://www.xiaohongshu.com/',
            'sec-ch-ua' => '"NotJA:Brand";v="8", "Chromium";v="138", "Google Chrome";v="138"',
            'sec-ch-ua-mobile' => '?0',
            'sec-ch-ua-platform' => '"Windows"',
            'sec-fetch-dest' => 'empty',
            'sec-fetch-mode' => 'cors',
            'sec-fetch-site' => 'same-site',
            'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36',
            'x-s' => 'XYS_2UQhPsHCH0c1PjhlHjIj2erjwjQhyoPTqBPT49pjHjIj2eHjwjQgynEDJ74AHjIj2ePjwjQTJdPIP/ZlgMrU4SmH4BTs8FSTzDR8anETLsTo49Tw8/QtaDV34FYGnLED/0WlLSQIPnSd/LzxG0HIpeWM/SZMqBpcy0mS/gYPGnYIyAZI4d4atMQx8eYPLLRoydY9/DQc474VzFDIJ9+8qr8fnSP9cdYk49Sc/M+zpFPhpMQ6aerl+948nfzs+bSrGpYMPSbeG9TPzDMLLDiF8fb8ygW7Jbq3tAQ08/Qc898/+ebk8FpIHjIj2ecjwjQ6GfkSG7cjKc==',
            'cookie' => 'abRequestId=4c94009d-7945-5232-b719-6788a8549f02; a1=198136015bbh8yuz305zwhahlypd9fhz2x38ne3mc50000303359; webId=378f683078c648c3ec20cfd9dd3bfc26; gid=yjYyqK824dJSyjYyqK8y2CdqDDxYv7uq82uEq6Sx0x1vUY28fjixuI888q8qq2j8Sqy4f4q2; webBuild=4.72.0; web_session=040069b903e6ae2440392b804c3a4b11f1299c; xsecappid=xhs-pc-web; loadts=1752811629499; unread={%22ub%22:%226879ba57000000002400c5aa%22%2C%22ue%22:%22685d60b00000000010010b69%22%2C%22uc%22:41}; websectiga=6169c1e84f393779a5f7de7303038f3b47a78e47be716e7bec57ccce17d45f99; sec_poison_id=46d9edca-2738-425b-ab3e-3e36d6dfea5d; acw_tc=0a4a7b8917528172917194569e58e1932ef9ae9bee2ffde63ed64164fac4f3',

        ];
    }

    public function searchAndSave(string $keyword, int $page = 1, int $pageSize = 20)
    {
        $result = $this->searchNotes($keyword, $page, $pageSize);
        if (!empty($result['data']['items'])) {
            $this->saveNote($result['data']['items'], $keyword);
        }
        return $result;
    }

    private function saveNote(array $items, string $keyword): void
    {
        foreach ($items as $item) {
            if(!isset($item['note_card']) || !isset($item['id'])){
                continue;
            }
            $noteId = $item['id'];

            if (Db::table('xhs_notes')->where('note_id', $noteId)->count()) {
                return;
            }
            Db::table('xhs_notes')->insert([
                'note_id' => $noteId,
                'keyword' => $keyword,
                'note_url' => 'https://www.xiaohongshu.com/explore/'.$noteId.'?xsec_token='.$item['xsec_token'],
                'cover_url' => $item['note_card']['cover']['url_default'] ?? ''
            ]);
        }
    }

    public function saveCozeData(array $param)
    {
        $noteId = $param['note_id'];
        is_array($param['note_image_list']) && $param['note_image_list'] = implode(',',$param['note_image_list']);
        is_array($param['note_tags']) && $param['note_tags'] = implode(',',$param['note_tags']);
        $data = [
            'note_id' => $param['note_id'],
            'auther_user_id' => $param['auther_user_id'],
            'auther_avatar' => $param['auther_avatar'],
            'auther_home_page_url' => $param['auther_home_page_url'],
            'auther_nick_name' => $param['auther_nick_name'],
            'note_card_type' => $param['note_card_type'],
            'note_display_title' => $param['note_display_title'],
            'note_desc' => $param['note_desc'],
            'note_duration' => $param['note_duration'],
            'note_image_list' => $param['note_image_list'],
            'note_model_type' => $param['note_model_type'],
            'note_tags' => $param['note_tags'],
            'note_url' => $param['note_url'],
            'video_a1_url' => $param['video_a1_url'],
            'video_h264_url' => $param['video_h264_url'],
            'video_h265_url' => $param['video_h265_url'],
            'video_h266_url' => $param['video_h266_url'],
            'video_id' => $param['video_id'],
            'note_create_time' => $param['note_create_time'],
            'raw_data' => json_encode($param),
            'is_detail' => 1
        ];
        if (Db::table('xhs_notes')->where('note_id', $noteId)->count()) {
            Db::table('xhs_notes')->where('note_id', $noteId)->update($data);
        }else{
            Db::table('xhs_notes')->insert($data);
        }
    }
}