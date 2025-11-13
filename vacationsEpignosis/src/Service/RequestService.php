<?php
namespace App\Service;
use App\Repository\RequestRepository;
use App\Helpers;
class RequestService {
    public function __construct(private RequestRepository $repo) {}
    public function list(array $claims): array {
        return (($claims['role']??'')==='manager') ? $this->repo->listAll() : $this->repo->listByUser($claims['sub']);
    }
    public function create(array $claims, array $data): array {
    if (($claims['role'] ?? '') !== 'employee')
        Helpers::json(403, ['error' => 'Only employees can create requests']);
    foreach (['start_date','end_date'] as $k)
        if (empty($data[$k]))
            Helpers::json(422, ['error' => "Missing field: $k"]);
    $start = strtotime((string)$data['start_date']);
    $end   = strtotime((string)$data['end_date']);
    if ($start === false || $end === false)
        Helpers::json(422, ['error' => 'Invalid date format']);
    if ($start > $end)
        Helpers::json(422, ['error' => 'start_date cannot be after end_date']);
    return $this->repo->create(
        $claims['sub'],
        $data['start_date'],
        $data['end_date'],
        $data['reason'] ?? null
    );
    }
    public function delete(array $claims, string $id): void {
        $row=$this->repo->findOwnerAndStatus($id);
        if(!$row) Helpers::json(404,['error'=>'Request not found']);
        if($row['user_id']!==($claims['sub']??'') || $row['status']!=='pending') Helpers::json(403,['error'=>'Forbidden']);
        $this->repo->delete($id);
    }
    public function approve(array $claims, string $id): array {
        $this->ensureManager($claims);
        $row=$this->repo->setStatus($id,'approved'); if(!$row) Helpers::json(404,['error'=>'Request not found']); return $row;
    }
    public function reject(array $claims, string $id): array {
        $this->ensureManager($claims);
        $row=$this->repo->setStatus($id,'rejected'); if(!$row) Helpers::json(404,['error'=>'Request not found']); return $row;
    }
    private function ensureManager(array $c){ if(($c['role']??'')!=='manager') Helpers::json(403,['error'=>'Forbidden']); }
}
