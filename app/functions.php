<?php

use App\Constants\ErrorCode;
use Hyperf\Constants\ConstantsCollector;

if (!function_exists("returnSuccess")) {
    function returnSuccess($data = [], $msg = 'success', $code = 200)
    {
        return [
            'code' => $code,
            'msg' => $msg,
            'data' => $data,
        ];
    }
}

if (!function_exists("returnError")) {
    function returnError($msg = 'error', $data = [], $code = 500)
    {
        return [
            'code' => $code,
            'msg' => $msg,
            'data' => $data,
        ];
    }
}

if (!function_exists("setPassword")) {
    function setPassword($password)
    {
        $salt = 'bcjkdhfdjfhsjkdfhsiovsjkdhfsfnkdsjcsdvbsdksdkj';
        return hash('sha256', $password . $salt);
    }
}

if (!function_exists("checkPassword")) {
    function checkPassword($password, $hash)
    {
        $salt = 'bcjkdhfdjfhsjkdfhsiovsjkdhfsfnkdsjcsdvbsdksdkj';
        return hash('sha256', $password . $salt) == $hash;
    }
}

if (!function_exists("getEnums")) {
    function getEnumMaps($className)
    {
        $data = ConstantsCollector::get($className);
        return array_map(fn($value) => $value["message"], $data);
    }
}