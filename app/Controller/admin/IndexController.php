<?php

namespace App\Controller\admin;

use Hyperf\HttpServer\Annotation\AutoController;

#[AutoController]
class IndexController
{
    public function index(){
        return [
            'path' => 'admin'
        ];
    }

}