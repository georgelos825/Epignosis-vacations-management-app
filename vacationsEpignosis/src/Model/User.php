<?php
namespace App\Model;
class User {
    public function __construct(
        public string $id,
        public string $name,
        public string $email,
        public string $employee_code,
        public string $role,
        public string $created_at
    ) {}
    public static function fromRow(array $r): self {
        return new self($r['id'],$r['name'],$r['email'],$r['employee_code'],$r['role'],$r['created_at']);
    }
    public function json(): array {
        return [
            'id'=>$this->id,'name'=>$this->name,'email'=>$this->email,
            'employee_code'=>$this->employee_code,'role'=>$this->role,'created_at'=>$this->created_at
        ];
    }
}
