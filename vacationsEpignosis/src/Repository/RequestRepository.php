<?php
namespace App\Repository;
use PDO;
class RequestRepository {
    public function __construct(private PDO $db) {}

    public function listAll(): array {
        $q="SELECT vr.*, u.name AS employee_name, u.email AS employee_email FROM vacation_requests vr JOIN users u ON u.id=vr.user_id ORDER BY submitted_at DESC";
        return $this->db->query($q)->fetchAll();
    }

    public function listByUser(string $userId): array {
        $st=$this->db->prepare("SELECT id,user_id,start_date,end_date,reason,status,submitted_at FROM vacation_requests WHERE user_id=? ORDER BY submitted_at DESC");
        $st->execute([$userId]); return $st->fetchAll();
    }

    public function create(string $userId,string $start,string $end,?string $reason): array {
        $st=$this->db->prepare("INSERT INTO vacation_requests (user_id,start_date,end_date,reason) VALUES (?,?,?,?) RETURNING id,user_id,start_date,end_date,reason,status,submitted_at");
        $st->execute([$userId,$start,$end,$reason]); return $st->fetch();
    }

    public function findOwnerAndStatus(string $id): ?array {
        $st=$this->db->prepare("SELECT user_id,status FROM vacation_requests WHERE id=?");
        $st->execute([$id]); $r=$st->fetch(); return $r?:null;
    }

    public function delete(string $id): void {
        $st=$this->db->prepare("DELETE FROM vacation_requests WHERE id=?"); $st->execute([$id]);
    }
    
    public function setStatus(string $id, string $status): ?array {
        $st=$this->db->prepare("UPDATE vacation_requests SET status=? WHERE id=? RETURNING id,user_id,start_date,end_date,reason,status,submitted_at");
        $st->execute([$status,$id]); return $st->fetch();
    }
}
