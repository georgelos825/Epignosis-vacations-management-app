<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use App\Repository\UserRepository;

require_once __DIR__ . '/../bootstrap.php';

final class UserRepositoryTest extends TestCase
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

    public function testCreateFindUpdateDeleteUser(): void
    {
        $repo = new UserRepository($this->pdo);
        // CREATE
        $u = $repo->create('Man One', 'man1@example.com', '0000123', 'supersecret9', 'manager');
        $this->assertArrayHasKey('id', $u);
        $userId = $u['id'];
        // LIST ALL should include the user
        $all = $repo->findAll();
        $this->assertTrue(collect($all)->contains(fn($row) => $row['id'] === $userId));
        // UPDATE (name + email)
        $upd = $repo->update($userId, ['name' => 'Man Uno', 'email' => 'man-uno@example.com']);
        $this->assertSame('Man Uno', $upd['name']);
        $this->assertSame('man-uno@example.com', $upd['email']);
        // DELETE
        $repo->delete($userId);
        $all2 = $repo->findAll();
        $this->assertFalse(collect($all2)->contains(fn($row) => $row['id'] === $userId));
    }

    public function testUniqueEmailAndEmployeeCode(): void
    {
        $repo = new UserRepository($this->pdo);
        $repo->create('A', 'unique1@example.com', '0000001', 'supersecret9', 'employee');
        $this->expectException(\PDOException::class); // 23505
        $repo->create('B', 'unique1@example.com', '0000002', 'supersecret9', 'employee');
    }
}

// μικρή helper συλλογή για άνετο contains()
function collect(array $a){ return new class($a){ function __construct(private $a){} function contains(callable $p){ foreach($this->a as $x){ if($p($x)) return true; } return false; } }; }
