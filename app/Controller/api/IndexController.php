<?php

namespace App\Controller\api;


use Hyperf\DbConnection\Db;
use Hyperf\HttpServer\Annotation\AutoController;

#[AutoController]
class IndexController
{
    public function index(){
        return [
            'path' => 'api',
            'data' => Db::table('sys_user')->where(['user_id' => 1])->get()
        ];
    }

}