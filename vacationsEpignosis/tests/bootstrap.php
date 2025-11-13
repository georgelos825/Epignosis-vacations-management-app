<?php
declare(strict_types=1);
define('PHPUNIT_RUNNING', true);
// PHPUnit bootstrapping
require_once __DIR__ . '/../vendor/autoload.php';

// Minimal environment overrides for tests
putenv('DB_HOST=localhost');
putenv('DB_PORT=5432');
putenv('DB_NAME=vacations_test');
putenv('DB_USER=postgres');
putenv('DB_PASS=postgres');

putenv('JWT_SECRET=testsecret');
putenv('JWT_ISS=vacations-api');
putenv('JWT_AUD=vacations-client');
putenv('JWT_TTL_MINUTES=60');
use Dotenv\Dotenv;

if (file_exists(__DIR__ . '/../.env')) {
    Dotenv::createImmutable(__DIR__ . '/../')->load();
}

function test_pdo(): PDO {
    return new PDO(
        sprintf(
            'pgsql:host=%s;port=%s;dbname=%s',
            getenv('DB_HOST') ?: 'localhost',
            getenv('DB_PORT') ?: 5432,
            getenv('DB_NAME') ?: 'vacations_test'
        ),
        getenv('DB_USER') ?: 'postgres',
        getenv('DB_PASS') ?: 'postgres'
    );
}
