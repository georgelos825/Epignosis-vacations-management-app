<?php
namespace App\Model;
class VacationRequest {
    public function __construct(
        public string $id,
        public string $user_id,
        public string $start_date,
        public string $end_date,
        public ?string $reason,
        public string $status,
        public string $submitted_at
    ) {}
    public static function fromRow(array $r): self {
        return new self($r['id'],$r['user_id'],$r['start_date'],$r['end_date'],$r['reason']??null,$r['status'],$r['submitted_at']);
    }
    public function json(): array { return get_object_vars($this); }
}
