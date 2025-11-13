<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use App\Repository\UserRepository;
use App\Service\AuthService;
use App\Auth;

require_once __DIR__ . '/../bootstrap.php';

final class AuthServiceTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        $this->pdo = new PDO(
            'pgsql:host=localhost;port=5432;dbname=vacations_test',
            'postgres',
            'postgres'
        );
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        // clean DB
        $this->pdo->exec("TRUNCATE users, vacation_requests RESTART IDENTITY CASCADE");
        // seed USING THE REPOSITORY so hashing/columns match production
        $repo = new UserRepository($this->pdo);
        $repo->create(
            'Manager',
            'manager@example.com',
            '0000001',
            'managerpass',
            'manager'
        );
    }

    private function repo(): UserRepository { return new UserRepository($this->pdo); }

    private function auth(): Auth {
        // keep these hardcoded for the test (or read from getenv set in tests/bootstrap.php)
        return new Auth('supersecret', 'vacations-api', 'vacations-client', 60);
    }

    private function svc(): AuthService { return new AuthService($this->repo(), $this->auth()); }

    public function testLoginLogoutAndSingleToken(): void
    {
        $svc = $this->svc();
        // First login should work
        $res1 = $svc->login('manager@example.com', 'managerpass');
        $this->assertArrayHasKey('token', $res1);
        // Second login (same user) must issue a different token (new jti)
        $res2 = $svc->login('manager@example.com', 'managerpass');
        $this->assertArrayHasKey('token', $res2);
        $this->assertNotSame($res1['token'], $res2['token']);
        // Decode & verify single-token enforcement via current_jti
        $claims1 = $this->auth()->verify($res1['token']);
        $claims2 = $this->auth()->verify($res2['token']);
        $st = $this->pdo->prepare("SELECT current_jti FROM users WHERE email=?");
        $st->execute(['manager@example.com']);
        $current = $st->fetchColumn();
        $this->assertNotEmpty($current);
        $this->assertNotSame($claims1['jti'], $current); // old token invalidated
        $this->assertSame($claims2['jti'], $current);    // latest is current
        // Logout should clear current_jti so res2 becomes invalid
        $svc->logout($claims2['sub']);
        $st->execute(['manager@example.com']);
        $after = $st->fetchColumn();
        $this->assertTrue($after === null || $after !== $claims2['jti']);
    }
   
    public function testInvalidCredentials(): void
    {
        // user exists but we'll pass wrong password
        $this->repo()->create('E', 'e@example.com', '0000777', 'supersecret9', 'employee');
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(401); // Helpers::json throws with HTTP code
        $this->expectExceptionMessageMatches('/Invalid credentials/');
        // This will now throw (caught by PHPUnit as expected)
        $this->svc()->login('e@example.com', 'wrong-pass');
    }
}
