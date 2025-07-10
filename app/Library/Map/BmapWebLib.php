<?php

namespace App\Library\Map;

use App\Exception\LogicException;
use App\Exception\ParametersException;
use App\Library\Contract\MapWebInterface;
use App\Service\RegionService;
use Hyperf\Cache\Annotation\Cacheable;
use Hyperf\Config\Annotation\Value;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Guzzle\ClientFactory;

class BmapWebLib implements MapWebInterface
{
    #[Value('map.bmap')]
    protected $config;

    #[Inject]
    protected ClientFactory $clientFactory;

    #[Inject]
    protected RegionService $regionService;

    protected string $baseUri = 'http://api.map.baidu.com/';

    public function __construct()
    {
        if (empty($this->config['key'])) {
            throw new ParametersException('请配置百度地图key');
        }
    }

    #[Cacheable(prefix: 'll_code', ttl: 3600)]
    public function getRegionInfoByLonLat($lon, $lat): array
    {
        $client = $this->clientFactory->create([
            'base_uri' => $this->baseUri,
            'timeout' => 5.0,
        ]);
        $response = $client->get('geocoder/v2', [
            'query' => [
                'ak' => $this->config['key'],
                'location' => sprintf('%.6f,%.6f', $lat, $lon),
                'output' => 'json',
                'pois' => 1
            ]
        ]);
        $res = json_decode($response->getBody()->getContents(), true);
        if ($res['status'] != 0) {
            throw new LogicException($res['message'] ?? '获取地址失败');
        }
        if (empty($res['result']['addressComponent']['adcode'])) {
            throw new LogicException('获取城市编码失败');
        }
        $city_info = [
            'adcode' => $res['result']['addressComponent']['adcode'],
            'province' => $res['result']['addressComponent']['province'],
            'city' => $res['result']['addressComponent']['city'],
            'district' => $res['result']['addressComponent']['district'],
            'address' => $res['result']['formatted_address'],
        ];
        $region = $this->regionService->getCityInfoByCode($city_info['adcode'], $city_info['city'] ?? '');
        $region = array_merge($region, $city_info);
        return $region;
    }

    #[Cacheable(prefix: 'ip_code', ttl: 3600)]
    public function getRegionInfoByIp($ip): array
    {
        $client = $this->clientFactory->create([
            'base_uri' => $this->baseUri,
            'timeout' => 5.0,
        ]);
        $response = $client->get('location/ip', [
            'query' => [
                'ak' => $this->config['key'],
                'ip' => $ip,
                'coor' => 'bd09ll'
            ]
        ]);
        $res = json_decode($response->getBody()->getContents(), true);
        if ($res['status'] != 0) {
            throw new LogicException($res['message'] ?? '获取地址失败');
        }
        if (empty($res['content']['address_detail']['adcode'])) {
            throw new LogicException('获取城市编码失败');
        }
        $city_info = [
            'address' => $res['content']['address'],
            'province' => $res['content']['address_detail']['province'],
            'city' => $res['content']['address_detail']['city'],
            'adcode' => $res['content']['address_detail']['adcode'],
            'lat' => $res['content']['point']['y'],
            'lon' => $res['content']['point']['x'],
        ];
        $region = $this->regionService->getCityInfoByCode($city_info['adcode'], $city_info['city'] ?? '');
        $region = array_merge($region, $city_info);
        return $region;
    }

    #[Cacheable(prefix: 'weather', ttl: 600)]
    public function getWeather($city): array
    {
        if (empty($city)) {
            throw new ParametersException('城市不能为空');
        }
        $client = $this->clientFactory->create([
            'base_uri' => $this->baseUri,
            'timeout' => 5.0,
        ]);
        $response = $client->get('weather/v1', [
            'query' => [
                'ak' => $this->config['key'],
                'district_id' => $city,
                'data_type' => 'now'
            ]
        ]);
        $res = json_decode($response->getBody()->getContents(), true);
        if ($res['status'] != 0) {
            throw new LogicException($res['message'] ?? '获取天气失败');
        }
        $weather = $res['result']['now'];
        return [
            'weather' => $weather['text'],
            'temperature' => $weather['temp'],
            'wind_direction' => $weather['wind_dir'],
            'wind_power' => $weather['wind_class'],
            'humidity' => $weather['rh'],
            'report_time' => date('Y-m-d H:i', strtotime($weather['uptime'])),
        ];
    }

}