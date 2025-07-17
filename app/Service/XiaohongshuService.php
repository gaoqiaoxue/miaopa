<?php


declare(strict_types=1);

namespace App\Service;

use Hyperf\Cache\Annotation\Cacheable;
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
            'x-s' => 'XYS_2UQhPsHCH0c1PjhlHjIj2erjwjQhyoPTqBPT49pjHjIj2eHjwjQgynEDJ74AHjIj2ePjwjQTJdPIP/ZlgMrU4SmH4bi6LbPlGLM+2BMzpoYEyfl6/BQe8Lltagzt89l6yec7z7zonfi9z7ShyURe2fGUcdSrwpYMagD92bc7/pZMPAbgLBS0GFu7+MYwG08fJDzzaeGF/9kIanhMGpS1GFQ9qbze+MS6agcELdSHLF4L+p4ap9keLnD9N9lwzDkQ4dZlnLkt4Lc7yAmwPnhEL7kgy0Q6zpk6Jn86JrELLeStyoqILS+YHjIj2ecjwjQ6GfkSG7cjKc==',
            'cookie' => 'abRequestId=4c94009d-7945-5232-b719-6788a8549f02; webBuild=4.72.0; xsecappid=xhs-pc-web; a1=198136015bbh8yuz305zwhahlypd9fhz2x38ne3mc50000303359; webId=378f683078c648c3ec20cfd9dd3bfc26; acw_tc=0a0bb4fb17526717178768943ea00ebaa9753f11224c1ac01b967215162dd7; gid=yjYyqK824dJSyjYyqK8y2CdqDDxYv7uq82uEq6Sx0x1vUY28fjixuI888q8qq2j8Sqy4f4q2; loadts=1752671839802; web_session=040069b903e6ae24403902ee423a4b9fa23ad4; unread={%22ub%22:%226852a0580000000011003b7c%22%2C%22ue%22:%22686e34650000000011003dd3%22%2C%22uc%22:29}; websectiga=82e85efc5500b609ac1166aaf086ff8aa4261153a448ef0be5b17417e4512f28; sec_poison_id=b6a9d51a-045a-445a-a57a-7ccf5cdb965f',

        ];
    }
}