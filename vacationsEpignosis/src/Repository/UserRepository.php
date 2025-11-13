<?php
namespace App\Repository;
use PDO;
use App\Model\User;
class UserRepository {
    public function __construct(private PDO $db) {}

    public function findByEmail(string $email): ?array {
        $st=$this->db->prepare("SELECT * FROM users WHERE email=?");
        $st->execute([$email]); $r=$st->fetch();
        return $r?:null;
    }

    public function findAll(): array {
        $st=$this->db->query("SELECT id,name,email,employee_code,role,created_at FROM users ORDER BY created_at DESC");
        return array_map(fn($r)=>User::fromRow($r)->json(), $st->fetchAll());
    }

    public function create(string $name,string $email,string $employee_code,string $password,string $role): array {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $st=$this->db->prepare("INSERT INTO users (name,email,employee_code,password_hash,role) VALUES (?,?,?,?,?) RETURNING id,name,email,employee_code,role,created_at");
        $st->execute([$name,$email,$employee_code,$hash,$role]);
        return $st->fetch();
    }

    public function update(string $id, array $fields): ?array {
    $sets=[]; $vals=[];
    if (isset($fields['name']))  { $sets[]="name=?";  $vals[]=$fields['name']; }
    if (isset($fields['email'])) { $sets[]="email=?"; $vals[]=$fields['email']; }
    if (isset($fields['password'])) {
        $sets[]="password_hash=?";
        $vals[] = password_hash($fields['password'], PASSWORD_BCRYPT);
    }

    if(!$sets) return null;
    $vals[]=$id;
    $sql="UPDATE users SET ".implode(',', $sets)." WHERE id=? 
          RETURNING id,name,email,employee_code,role,created_at";
    $st=$this->db->prepare($sql);
    $st->execute($vals);
    return $st->fetch();
    }

    public function delete(string $id): void {
        $st=$this->db->prepare("DELETE FROM users WHERE id=?"); $st->execute([$id]);
    }

    public function getCurrentJti(string $userId): ?string {
    $st = $this->db->prepare("SELECT current_jti FROM users WHERE id = ?");
    $st->execute([$userId]);
    $r = $st->fetch();
    return $r ? ($r['current_jti'] ?? null) : null;
    }

    public function setCurrentJti(string $userId, string $jti): void {
    $st = $this->db->prepare("UPDATE users SET current_jti = ? WHERE id = ?");
    $st->execute([$jti, $userId]);
    }
    
    public function clearCurrentJti(string $userId): void {
    $st = $this->db->prepare("UPDATE users SET current_jti = NULL WHERE id = ?");
    $st->execute([$userId]);
    }
}
