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

class TmapWebLib implements MapWebInterface
{

    #[Value('map.tmap')]
    protected $config;

    #[Inject]
    protected ClientFactory $clientFactory;

    #[Inject]
    protected RegionService $regionService;

    protected string $baseUri = 'https://apis.map.qq.com/';

    public function __construct()
    {
        if (empty($this->config['key'])) {
            throw new ParametersException('请配置腾讯地图key');
        }
    }

    #[Cacheable(prefix: 'll_code', ttl: 3600)]
    public function getRegionInfoByLonLat($lon, $lat): array
    {
        $client = $this->clientFactory->create([
            'base_uri' => $this->baseUri,
            'timeout' => 5.0,
        ]);
        $response = $client->get('ws/geocoder/v1', [
            'query' => [
                'key' => $this->config['key'],
                'location' => sprintf('%.6f,%.6f', $lat, $lon),
                'get_poi' => 0
            ]
        ]);
        $res = json_decode($response->getBody()->getContents(), true);
        if ($res['status'] != 0) {
            throw new LogicException($res['message'] ?? '获取地址失败');
        }
        if (empty($res['result']['ad_info']['adcode'])) {
            throw new LogicException('获取城市编码失败');
        }
        $city_info = [
            'adcode' => $res['result']['ad_info']['adcode'],
            'province' => $res['result']['ad_info']['province'],
            'city' => $res['result']['ad_info']['city'],
            'district' => $res['result']['ad_info']['district'],
            'address' => $res['result']['address'],
        ];
        $region = $this->regionService->getCityInfoByCode($city_info['adcode'], $city_info['city'] ?? '');
        $region = array_merge($region, $city_info);
        return $region;
    }

    #[Cacheable(prefix: 'address_parse', ttl: 86400)] // 缓存24小时
    public function getLatLonByAddress($address): array
    {
        $client = $this->clientFactory->create([
            'base_uri' => 'https://apis.map.qq.com/',
            'timeout' => 5.0,
        ]);
        $response = $client->get('ws/geocoder/v1/', [
            'query' => [
                'key' => $this->config['key'], // 腾讯地图API key
                'address' => $address,
                'output' => 'json'
            ]
        ]);
        $res = json_decode($response->getBody()->getContents(), true);
        if ($res['status'] != 0) {
            throw new LogicException($res['message'] ?? '地址解析失败');
        }
        $result = $res['result'];
        $location = $result['location'];
        return [
            'lon' => $location['lng'], // 经度
            'lat' => $location['lat'], // 纬度
            'address' => $result['address'] ?? $address,
            'formatted_address' => $result['title'] ?? $address,
            'adcode' => $result['ad_info']['adcode'] ?? '',
            'province' => $result['ad_info']['province'] ?? '',
            'city' => $result['ad_info']['city'] ?? '',
            'district' => $result['ad_info']['district'] ?? '',
            'reliability' => $result['reliability'] ?? 0, // 可信度(1-10)
            'level' => $result['level'] ?? 0, // 地址类型
        ];
    }

    #[Cacheable(prefix: 'ip_code', ttl: 3600)]
    public function getRegionInfoByIp($ip): array
    {
        $client = $this->clientFactory->create([
            'base_uri' => $this->baseUri,
            'timeout' => 5.0,
        ]);
        $response = $client->get('ws/location/v1/ip', [
            'query' => [
                'key' => $this->config['key'],
                'ip' => $ip,
            ]
        ]);
        $res = json_decode($response->getBody()->getContents(), true);
        if ($res['status'] != 0) {
            throw new LogicException($res['message'] ?? '获取地址失败');
        }
        if (empty($res['result']['ad_info']['adcode'])) {
            throw new LogicException('获取城市编码失败');
        }
        $ad_info = $res['result']['ad_info'];
        $city_info = [
            'adcode' => $ad_info['adcode'],
            'province' => $ad_info['province'],
            'city' => $ad_info['city'],
            'district' => $ad_info['district'],
            'address' => $ad_info['province'].$ad_info['city'].$ad_info['district'],
            'lat' => $res['result']['location']['lat'],
            'lon' => $res['result']['location']['lng'],
        ];
        $region = $this->regionService->getCityInfoByCode($city_info['adcode'], $city_info['city'] ?? '');
        $region = array_merge($region, $city_info);
        return $region;
    }

    public function getWeather($city): array
    {
        throw new ParametersException('腾讯地图暂不支持天气查询');
    }

}