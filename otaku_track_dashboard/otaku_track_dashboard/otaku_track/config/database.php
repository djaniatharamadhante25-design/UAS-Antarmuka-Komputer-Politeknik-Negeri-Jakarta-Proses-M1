<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';

    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        // Samakan zona waktu sesi MySQL dengan PHP (Asia/Jakarta) supaya filter "7 hari terakhir" di dashboard akurat.
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET time_zone = '" . (new DateTime('now'))->format('P') . "'",
    ]);

    return $pdo;
}
