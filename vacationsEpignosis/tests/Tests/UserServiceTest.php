<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use App\Repository\UserRepository;
use App\Service\UserService;

require_once __DIR__ . '/../bootstrap.php';

final class UserServiceTest extends TestCase
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
        $this->pdo->exec("TRUNCATE users, vacation_requests RESTART IDENTITY CASCADE");
        $this->pdo->exec("TRUNCATE users RESTART IDENTITY CASCADE");
    }

    private function svc(): UserService {
        return new UserService(new UserRepository($this->pdo));
    }

    private function managerClaims(): array {
        return ['role' => 'manager', 'sub' => 'dummy'];
    }

    public function testCreateValidations(): void
    {
        $svc = $this->svc();
        // Missing fields
        try {
            $svc->create($this->managerClaims(), []);
            $this->fail('Expected validation error');
        } catch (\Throwable $e) {
            $this->assertStringContainsString('Missing field', $e->getMessage());
        }
        // Bad email, short pwd, wrong code
        try {
            $svc->create($this->managerClaims(), [
                'name'=>'N', 'email'=>'bad', 'employee_code'=>'12A', 'password'=>'short', 'role'=>'employee'
            ]);
            $this->fail('Expected validation error');
        } catch (\Throwable $e) {
            $msg = $e->getMessage();
            $this->assertStringContainsString('Invalid email format', $msg);
            $this->assertStringContainsString('Password must be at least 9 characters', $msg);
            $this->assertStringContainsString('employee_code must be exactly 7 digits', $msg);
        }
        // OK
        $u = $svc->create($this->managerClaims(), [
            'name'=>'Good', 'email'=>'good@example.com', 'employee_code'=>'0000123', 'password'=>'longpassword9', 'role'=>'employee'
        ]);
        $this->assertSame('Good', $u['name']);
    }

    public function testUpdateValidations(): void
    {
        $svc = $this->svc();
        $u = $svc->create($this->managerClaims(), [
            'name'=>'U', 'email'=>'u@example.com', 'employee_code'=>'0000456', 'password'=>'longpassword9', 'role'=>'employee'
        ]);
        // Bad email & short pwd & bad code
        try {
            $svc->update($this->managerClaims(), $u['id'], [
                'email'=>'x', 'password'=>'short', 'employee_code'=>'12'
            ]);
            $this->fail('Expected validation error');
        } catch (\Throwable $e) {
            $msg = $e->getMessage();
            $this->assertStringContainsString('Invalid email format', $msg);
            $this->assertStringContainsString('Password must be at least 9 characters', $msg);
            $this->assertStringContainsString('employee_code cannot be changed', $msg);
        }
    }
}
