<?php

namespace App\Controller\api;

use App\Controller\AbstractController;
use App\Library\Contract\AuthTokenInterface;
use App\Service\UserService;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\AutoController;

#[AutoController]
class UserController extends AbstractController
{
    #[Inject]
    protected UserService $service;

    public function search(AuthTokenInterface $authToken):array
    {
        $keyword = $this->request->input('keyword');
        $payload = $authToken->getUserData('default', false);
        $user_id = $payload['jwt_claims']['user_id'] ?? 0;
        $result = $this->service->getApiList(['keyword' => $keyword, 'user_id' => $user_id]);
        return returnSuccess($result);
    }
}