<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use App\Repository\RequestRepository;

final class RequestRepositoryTest extends TestCase
{
    private PDO $pdo;
    private RequestRepository $repo;
    private string $uid;

    protected function setUp(): void
    {
        $this->pdo = new PDO('pgsql:host=localhost;port=5432;dbname=vacations_test','postgres','postgres');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->exec("TRUNCATE users, vacation_requests RESTART IDENTITY CASCADE");
        // seed 1 user
        $this->uid = '11111111-1111-1111-1111-111111111111';
        $hash = password_hash('passssss9', PASSWORD_BCRYPT);
        $this->pdo->exec("
          INSERT INTO users (id,name,email,employee_code,password_hash,role)
          VALUES ('{$this->uid}','Emp','emp@example.com','0000123','{$hash}','employee')
        ");
        $this->repo = new RequestRepository($this->pdo);
    }

    public function testCreateListApproveRejectDelete(): void
    {
        // create
        $r = $this->repo->create($this->uid, '2025-11-20', '2025-11-22', 'vac');
        $this->assertNotEmpty($r['id']);
        $this->assertSame('pending', $r['status']);
        // listByUser sees it
        $mine = $this->repo->listByUser($this->uid);
        $this->assertCount(1, $mine);
        $this->assertSame('2025-11-20', $mine[0]['start_date']);
        // approve
        $approved = $this->repo->setStatus($r['id'], 'approved');
        $this->assertSame('approved', $approved['status']);
        // reject (flip again)
        $rejected = $this->repo->setStatus($r['id'], 'rejected');
        $this->assertSame('rejected', $rejected['status']);
        // delete
        $this->repo->delete($r['id']);
        $after = $this->repo->listByUser($this->uid);
        $this->assertCount(0, $after);
    }
}
