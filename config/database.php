<?php
declare(strict_types=1);

/**
 * FILE: config/database.php
 * Nhiệm vụ: Khởi tạo kết nối MySQL duy nhất cho toàn dự án Hanamiao bằng PDO.
 */

function getConnection(): PDO
{
    static $connection = null;

    if ($connection instanceof PDO) {
        return $connection;
    }

    $host = '127.0.0.1';
    $port = '3306';
    $database = 'hanamiao_db';
    $username = 'root';
    $password = '';

    $dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";

    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    $connection = new PDO($dsn, $username, $password, $options);
    return $connection;
}

