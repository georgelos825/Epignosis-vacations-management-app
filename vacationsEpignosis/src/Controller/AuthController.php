<?php
namespace App\Controller;
use App\Helpers;
use App\Service\AuthService;
class AuthController {
    public function __construct(private AuthService $svc) {}

    public function login() {
        $b=Helpers::body();
        foreach(['email','password'] as $k) if(empty($b[$k])) Helpers::json(422,['error'=>"Missing field: $k"]);
        $res=$this->svc->login($b['email'],$b['password']);
        Helpers::json(200,$res);
    }

    public function logout(array $claims) {
         $userId = $claims['sub'] ?? null;
         if (!$userId) \App\Helpers::json(401, ['error'=>'Unauthorized']);
         $this->svc->logout($userId);          // calls clearCurrentJti
         \App\Helpers::json(204, null);
    }
}
