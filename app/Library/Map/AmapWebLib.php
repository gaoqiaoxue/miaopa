<?php

namespace App\Library\Map;

use App\Exception\LogicException;
use App\Exception\ParametersException;
use App\Library\Contract\MapWebInterface;
use App\Service\RegionService;
use Hyperf\Cache\Annotation\Cacheable;
use Hyperf\Config\Annotation\Value;
use Hyperf\DbConnection\Db;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Guzzle\ClientFactory;

class AmapWebLib implements MapWebInterface
{
    #[Value('map.amap')]
    protected $config;

    #[Inject]
    protected ClientFactory $clientFactory;

    #[Inject]
    protected RegionService $regionService;

    protected string $baseUri = 'https://restapi.amap.com/v3/';

    public function __construct()
    {
        if (empty($this->config['key'])) {
            throw new ParametersException('请配置高德地图key');
        }
    }

    #[Cacheable(prefix: 'll_code', ttl: 3600)]
    public function getRegionInfoByLonLat($lon, $lat): array
    {
        $client = $this->clientFactory->create([
            'base_uri' => $this->baseUri,
            'timeout' => 5.0,
        ]);
        $response = $client->get('geocode/regeo', [
            'query' => [
                'key' => $this->config['key'],
                'location' => sprintf('%.6f,%.6f', $lon, $lat),
                'output' => 'json'
            ]
        ]);
        $res = json_decode($response->getBody()->getContents(), true);
        if ($res['status'] != 1) {
            throw new LogicException($res['info'] ?? '获取地址失败');
        }
        $address = $res['regeocode']['addressComponent'];
        if(empty($address['adcode'])){
            throw new LogicException('获取城市编码失败');
        }
        $city_info = [
            'adcode' => $address['adcode'],
            'province' => $address['province'],
            'city' => empty($address['city']) ? $address['province'] : $address['city'],
            'district' => $address['district'] ?? '',
            'address' => $res['regeocode']['formatted_address'],
        ];
        $region = $this->regionService->getCityInfoByCode($city_info['adcode'],$city_info['city']??'');
        $region = array_merge($region,$city_info);
        return $region;
    }

    #[Cacheable(prefix: 'address_parse', ttl: 86400)] // 缓存24小时
    public function getLatLonByAddress($address): array
    {
        $client = $this->clientFactory->create([
            'base_uri' => $this->baseUri,
            'timeout' => 5.0,
        ]);
        $response = $client->get('geocode/geo', [
            'query' => [
                'key' => $this->config['key'],
                'address' => $address,
                'output' => 'json'
            ]
        ]);
        $res = json_decode($response->getBody()->getContents(), true);
        if ($res['status'] != '1' || empty($res['geocodes'])) {
            throw new LogicException($res['info'] ?? '获取经纬度失败');
        }
        $location = explode(',', $res['geocodes'][0]['location']);
        if (count($location) !== 2) {
            throw new LogicException('经纬度格式错误');
        }
        return [
            'lon' => $location[0], // 经度
            'lat' => $location[1], // 纬度
            'formatted_address' => $res['geocodes'][0]['formatted_address'] ?? '',
            'province' => $res['geocodes'][0]['province'] ?? '',
            'city' => $res['geocodes'][0]['city'] ?? '',
            'district' => $res['geocodes'][0]['district'] ?? '',
            'adcode' => $res['geocodes'][0]['adcode'] ?? '',
        ];
    }

    #[Cacheable(prefix: 'ip_code', ttl: 3600)]
    public function getRegionInfoByIp($ip):array
    {
        $client = $this->clientFactory->create([
            'base_uri' => $this->baseUri,
            'timeout' => 5.0,
        ]);
        $response = $client->get('ip', [
            'query' => [
                'key' => $this->config['key'],
                'ip' => $ip,
                'output' => 'json'
            ]
        ]);
        $res = json_decode($response->getBody()->getContents(), true);
        if ($res['status'] != 1) {
            throw new LogicException($res['info'] ?? '获取地址失败');
        }
        $latlon = [];
        $rectangle = explode(';', $res['rectangle']);
        !empty($rectangle[0]) && $latlon = explode(',', $rectangle[0]);
        if(empty($res['adcode'])){
            throw new LogicException('获取城市编码失败');
        }
        $city_info = [
            'adcode' => $res['adcode'],
            'province' => $res['province'],
            'city' => $res['city'],
            'address' => $res['province'] . $res['city'],
            'lat' => $latlon[1] ?? '',
            'lon' => $latlon[0] ?? '',
        ];
        $region = $this->regionService->getCityInfoByCode($res['adcode'],$city_info['city']??'');
        $region = array_merge($region,$city_info);
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
        $response = $client->get('weather/weatherInfo', [
            'query' => [
                'key' => $this->config['key'],
                'city' => $city,
                'output' => 'json'
            ]
        ]);
        $res = json_decode($response->getBody()->getContents(), true);
        if ($res['status'] != 1) {
            throw new LogicException($res['info'] ?? '获取天气失败');
        }
        $weather = $res['lives'][0];
        return [
            'weather' => $weather['weather'], // 天气现象（汉字描述）
            'temperature' => $weather['temperature'], // 实时气温，单位：摄氏度
            'wind_direction' => $weather['winddirection'], // 风向描述
            'wind_power' => $weather['windpower'], // 风力级别，单位：级
            'humidity' => $weather['humidity'], // 空气湿度
            'report_time' => $weather['reporttime'], // 数据发布的时间
        ];
    }
}