<?php
namespace App\Service;
use App\Repository\UserRepository;
use App\Auth;
use App\Helpers;
class AuthService {
    public function __construct(private UserRepository $users, private Auth $auth) {}

     public function login(string $email, string $password): array {
        $u = $this->users->findByEmail($email);
        if (!$u || !password_verify($password, $u['password_hash'])) {
            Helpers::json(401, ['error' => 'Invalid credentials']);
        }
        // SINGLE-TOKEN ENFORCEMENT
        $jti = bin2hex(random_bytes(16)); // 32-hex chars
        $this->users->setCurrentJti($u['id'], $jti);
        $jwt = $this->auth->token($u['id'], $u['role'], $u['email'], $jti);
        return ['token' => $jwt, 'role' => $u['role']];
    }

     public function logout(string $userId): void {
        // jti deleted on logout (check db to see it in real time)
        $this->users->clearCurrentJti($userId);
    }
}
