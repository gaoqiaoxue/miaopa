<?php

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

if(!function_exists("setPassword")) {
    function setPassword($password)
    {
        return password_hash($password, PASSWORD_BCRYPT);
    }
}

if(!function_exists("checkPassword")) {
    function checkPassword($password, $hash)
    {
        return password_verify($password, $hash);
    }

}