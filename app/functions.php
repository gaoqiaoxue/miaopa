<?php

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

if (!function_exists('paginateTransformer')) {
    function paginateTransformer(LengthAwarePaginatorInterface $data): array
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

if (!function_exists('getClientIp')) {
    function getClientIp()
    {
        $request = \Hyperf\Context\Context::get(\Psr\Http\Message\ServerRequestInterface::class);
        // 处理代理情况
        $res = $request->getHeaders();
        var_dump($res);
        if (isset($res['http_client_ip'])) {
            return $res['http_client_ip'];
        } elseif (isset($res['x-real-ip'])) {
            return $res['x-real-ip'];
        } elseif (isset($res['x-forwarded-for'])) {
            return $res['x-forwarded-for'];
        } elseif (isset($res['http_x_forwarded_for'])) {
            //部分CDN会获取多层代理IP，所以转成数组取第一个值
            $arr = explode(',', $res['http_x_forwarded_for']);
            return $arr[0];
        } else {
            // return $res['remote_addr'];
            $serverParams = $request->getServerParams();
            var_dump($serverParams);
            return $serverParams['remote_addr'] ?? '';
        }
    }
}

if (!function_exists('arrayToTree')) {
    function arrayToTree(
        array      $items,
        int|string $parentId = 0,
        string     $idKey = 'id',
        string     $parentKey = 'parent_id',
        string     $childrenKey = 'children'
    ): array
    {
        $branch = [];
        foreach ($items as $k => $item) {
            if (is_object($item)) {
                if ($item->$parentKey == $parentId) {
                    $children = arrayToTree($items, $item->$idKey, $idKey, $parentKey, $childrenKey);
                    if ($children) {
                        $item->$childrenKey = $children;
                    }
                    $branch[] = $item;
                    unset($items[$k]);
                }
            } else {
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

if (!function_exists('getAvatar')) {
    function getAvatar($avatar)
    {
        if (empty($avatar)) {
            return \Hyperf\Support\env('FILE_HOST') . '/uploads/default_avatar.png';
        } elseif (is_numeric($avatar)) {
            return \App\Service\FileService::getFileInfoById($avatar);
        } else {
            return generateFileUrl($avatar);
        }
    }
}

if (!function_exists('generateFileUrl')) {
    function generateFileUrl($url)
    {
        if (empty($url)) {
            return '';
        } elseif (preg_match('/^http(s)?:\/\//i', $url)) {
            return $url;
        } elseif (preg_match('/^uploads\//i', $url)) {
            return \Hyperf\Support\env('FILE_HOST') . '/' . trim($url, '/');
        } else {
            return $url;
        }
    }
}

if (!function_exists('getPublishTime')) {
    function getPublishTime($date)
    {
        $diff = time() - $date;
        if ($diff < 60) {
            return '刚刚';
        } elseif ($diff < 3600) {
            return floor($diff / 60) . '分钟前';
        } elseif ($diff < 86400) {
            return floor($diff / 3600) . '小时前';
        } elseif ($diff < 86400 * 7) {
            return floor($diff / 86400) . '天前';
        } else {
            return date('Y-m-d', $date);
        }
    }
}
