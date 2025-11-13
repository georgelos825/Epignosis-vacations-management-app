<?php
declare(strict_types=1);

namespace App;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use DateTimeImmutable;

class Auth {
    public function __construct(
        private string $secret,
        private string $iss,
        private string $aud,
        private int $ttl
    ) {}
 
    public function token(string $userId, string $role, string $email, string $jti): string {
        $now = new DateTimeImmutable();
        $payload = [
            'iss'   => $this->iss,
            'aud'   => $this->aud,
            'iat'   => $now->getTimestamp(),
            'nbf'   => $now->getTimestamp(),
            'exp'   => $now->modify("+{$this->ttl} minutes")->getTimestamp(),
            'sub'   => $userId,
            'role'  => $role,
            'email' => $email,
            'jti'   => $jti,
        ];
        return JWT::encode($payload, $this->secret, 'HS256');
    }

    public function verify(string $jwt): array {
        return (array) JWT::decode($jwt, new Key($this->secret, 'HS256'));
    }
}
