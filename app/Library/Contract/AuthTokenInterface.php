<?php

namespace App\Library\Contract;

interface AuthTokenInterface
{

    /**
     * @param $userData
     * @param $scene
     * @return array
     */
    public function createToken($userData, string $scene = 'default'): array;

    /**
     * @return array
     */
    public function refreshToken(): array;

    /**
     * @return mixed
     */
    public function logout(): bool;


    /**
     * @return array
     */
    public function getUserData(string $scene = 'default', bool $force = true): array;

}