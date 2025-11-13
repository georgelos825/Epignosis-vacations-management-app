<?php
declare(strict_types=1);

namespace App\Service;

use App\Repository\UserRepository;
use App\Helpers;
use PDOException;

class UserService
{
    public function __construct(private UserRepository $users) {}

    public function list(array $claims): array
    {
        self::ensureManager($claims);
        return $this->users->findAll();
    }

    public function create(array $claims, array $data): array
    {
        self::ensureManager($claims);
        $errors = [];
        // Required fields
        foreach (['name','email','employee_code','password','role'] as $k) {
            if (!isset($data[$k]) || $data[$k] === '' || $data[$k] === null) {
                $errors[] = "Missing field: {$k}";
            }
        }
        // Email format
        if (!filter_var((string)$data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Invalid email format";
        }
        // Password rules: no whitespace + min length 9
        $pwd = (string)$data['password'];
        if (preg_match('/\s/', $pwd)) {
            $errors[] = "Password must not contain spaces";
        }
        if (mb_strlen($pwd) < 9) {
            $errors[] = "Password must be at least 9 characters";
        }
        // employee_code: exactly 7 digits (leading zeros allowed)
        if (!preg_match('/^\d{7}$/', (string)$data['employee_code'])) {
            $errors[] = "employee_code must be exactly 7 digits";
        }
        // Role
        if (!in_array($data['role'], ['manager','employee'], true)) {
            $errors[] = "Invalid role";
        }
        if ($errors) Helpers::json(422, ['errors' => $errors]);
        try {
            return $this->users->create(
                (string)$data['name'],
                (string)$data['email'],
                (string)$data['employee_code'],
                $pwd,
                (string)$data['role']
            );
        } catch (PDOException $e) {
            // 23505 = unique_violation (Postgres)
            if ($e->getCode() === '23505') {
                $msg = 'Duplicate value';
                $detail = $e->errorInfo[2] ?? '';
                if (stripos($detail, 'users_email_unique') !== false) {
                    $msg = 'Email already exists';
                } elseif (stripos($detail, 'users_employee_code_unique') !== false) {
                    $msg = 'employee_code already exists';
                }
                Helpers::json(409, ['error' => $msg]);
            }
            throw $e;
        }
    }

    public function update(array $claims, string $id, array $data): array {
    self::ensureManager($claims);
    // αγνόησε τυχόν role που έστειλε ο client
    unset($data['role']);
    $errors = [];
    if (isset($data['email']) && !filter_var((string)$data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }
    if (isset($data['password'])) {
        $pwd = (string)$data['password'];
        if (preg_match('/\s/', $pwd)) $errors[] = "Password must not contain spaces";
        if (mb_strlen($pwd) < 9)     $errors[] = "Password must be at least 9 characters";
    }
    if (isset($data['employee_code'])) {
        $errors[] = "employee_code cannot be changed";
        Helpers::json(422, ['errors'=>$errors]);
    }
    if ($errors) Helpers::json(422, ['errors'=>$errors]);
    try {
        $row = $this->users->update($id, $data);
    } catch (\PDOException $e) {
        if ($e->getCode()==='23505') {
            $msg='Duplicate value';
            $detail=$e->errorInfo[2]??'';
            if (stripos($detail,'users_email_unique')!==false) $msg='Email already exists';
            Helpers::json(409, ['error'=>$msg]);
        }
        throw $e;
    }
    if (!$row) Helpers::json(404, ['error'=>'User not found or no fields to update']);
    return $row;
    }

    public function delete(array $claims, string $id): void
    {
        self::ensureManager($claims);
        if ($id === ($claims['sub'] ?? '')) {
        Helpers::json(403, ['error' => 'You cannot delete yourself']);
        }
        $this->users->delete($id);
    }

    private static function ensureManager(array $c): void
    {
        if (($c['role'] ?? '') !== 'manager') {
            Helpers::json(403, ['error' => 'Forbidden']);
        }
    }
}