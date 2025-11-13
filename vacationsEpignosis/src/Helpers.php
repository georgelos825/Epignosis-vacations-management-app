<?php
namespace App;
class Helpers {
    public static function json(int $status, $data) {
        http_response_code($status);
        header('Content-Type: application/json');
        // If running under PHPUnit, throw an exception instead of exiting
         if (defined('PHPUNIT_RUNNING')) {
           throw new \RuntimeException(json_encode($data), $status);
         }
         echo json_encode($data);
        exit;
    }

    public static function body(): array {
        $raw = file_get_contents('php://input');
        $json = json_decode($raw,true);
        return is_array($json)?$json:[];
    }
    
    public static function requireEnv(string $key): string {
    $val = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
    if ($val === false || $val === '') {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode(['error' => "Missing environment variable: {$key}"]);
        exit;
    }
    return $val;
    }
}
