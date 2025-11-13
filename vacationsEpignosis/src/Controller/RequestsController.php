<?php
namespace App\Controller;
use App\Helpers;
use App\Service\RequestService;
class RequestsController {
    public function __construct(private RequestService $svc) {}
    public function list(array $claims){ Helpers::json(200,$this->svc->list($claims)); }
    public function create(array $claims){ Helpers::json(201,$this->svc->create($claims, Helpers::body())); }
    public function delete(array $claims, string $id){ $this->svc->delete($claims,$id); Helpers::json(204,null); }
    public function approve(array $claims, string $id){ Helpers::json(200,$this->svc->approve($claims,$id)); }
    public function reject(array $claims, string $id){ Helpers::json(200,$this->svc->reject($claims,$id)); }
}
