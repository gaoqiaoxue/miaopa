<?php

use Hyperf\Constants\ConstantsCollector;
use Hyperf\Contract\LengthAwarePaginatorInterface;

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

if(!function_exists('paginateTransformer'))
{
    function paginateTransformer(LengthAwarePaginatorInterface $data):array
    {
        return [
            'items' => $data->items(),
            'total' => $data->total(),
            'last_page' => $data->lastPage(),
            'current_page' => $data->currentPage(),
            'per_page' => $data->perPage(),
        ];
    }
}


if(!function_exists('arrayToTree'))
{
    function arrayToTree(
        array $items,
        int|string $parentId = 0,
        string $idKey = 'id',
        string $parentKey = 'parent_id',
        string $childrenKey = 'children'
    ): array {
        $branch = [];
        foreach ($items as $k => $item) {
            if(is_object($item)) {
                if ($item->$parentKey == $parentId) {
                    $children = arrayToTree($items, $item->$idKey, $idKey, $parentKey, $childrenKey);
                    if ($children) {
                        $item->$childrenKey = $children;
                    }
                    $branch[] = $item;
                    unset($items[$k]);
                }
            }else{
                if ($item[$parentKey] == $parentId) {
                    $children = arrayToTree($items, $item[$idKey], $idKey, $parentKey, $childrenKey);
                    if ($children) {
                        $item[$childrenKey] = $children;
                    }
                    $branch[] = $item;
                    unset($items[$k]);
                }
            }
        }
        return $branch;
    }
}
