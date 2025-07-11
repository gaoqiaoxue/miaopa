<?php

namespace App\Library\Contract;

interface MapWebInterface
{
    public function getRegionInfoByLonLat($lon, $lat): array;

    public function getLatLonByAddress($address): array;

    public function getRegionInfoByIp($ip): array;

    public function getWeather($city): array;

}