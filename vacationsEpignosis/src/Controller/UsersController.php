<?php
namespace App\Controller;
use App\Helpers;
use App\Service\UserService;
class UsersController {
    public function __construct(private UserService $svc) {}
    public function list(array $claims){ Helpers::json(200,$this->svc->list($claims)); }
    public function create(array $claims){ Helpers::json(201,$this->svc->create($claims, Helpers::body())); }
    public function update(array $claims, string $id){ Helpers::json(200,$this->svc->update($claims,$id, Helpers::body())); }
    public function delete(array $claims, string $id){ $this->svc->delete($claims,$id); Helpers::json(204,null); }
}
