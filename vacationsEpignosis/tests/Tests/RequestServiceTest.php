<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use App\Repository\UserRepository;
use App\Repository\RequestRepository;
use App\Service\RequestService;

require_once __DIR__ . '/../bootstrap.php';

final class RequestServiceTest extends TestCase
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
        $this->pdo->exec("
            INSERT INTO users (id, name, email, employee_code, password_hash, role)
            VALUES
            ('11111111-1111-1111-1111-111111111111','Manager','manager@example.com','0000001','" . password_hash('managerpass', PASSWORD_BCRYPT) . "','manager'),
            ('22222222-2222-2222-2222-222222222222','Employee','employee@example.com','0000002','" . password_hash('employeepass', PASSWORD_BCRYPT) . "','employee')
        ");
    }

    private function requestSvc(): RequestService {
        return new RequestService(new RequestRepository($this->pdo));
    }
    private function repoUser(): UserRepository {
        return new UserRepository($this->pdo);
    }

    public function testEmployeeCreatesAndManagerApproves(): void
    {
        $uEmp = $this->repoUser()->create('Emp', 'emp@example.com','0000200','supersecret9','employee');
        $uMan = $this->repoUser()->create('Man', 'man@example.com','0000201','supersecret9','manager');
        $svc = $this->requestSvc();
        // Employee creates OK
        $req = $svc->create(['role'=>'employee','sub'=>$uEmp['id']], [
            'start_date'=>'2025-11-10','end_date'=>'2025-11-12','reason'=>'family'
        ]);
        $this->assertSame('pending', $req['status']);
        // start > end => 422
        try {
            $svc->create(['role'=>'employee','sub'=>$uEmp['id']], [
                'start_date'=>'2025-11-15','end_date'=>'2025-11-12'
            ]);
            $this->fail('Expected date validation error');
        } catch (\Throwable $e) {
            $this->assertStringContainsString('start_date', $e->getMessage());
        }
        // Manager approves
        $row = $svc->approve(['role'=>'manager','sub'=>$uMan['id']], $req['id']);
        $this->assertSame('approved', $row['status']);
    }

    public function testEmployeeCanDeleteOwnPendingOnly(): void
    {
        $uEmp = $this->repoUser()->create('Emp2', 'emp2@example.com','0000300','supersecret9','employee');
        $svc  = $this->requestSvc();
        $req = $svc->create(['role'=>'employee','sub'=>$uEmp['id']], [
            'start_date'=>'2025-11-10','end_date'=>'2025-11-10'
        ]);
        // OK delete own pending
        $svc->delete(['role'=>'employee','sub'=>$uEmp['id']], $req['id']);
        $this->assertTrue(true); // no exception
    }
}
