<?php
// =============================================================
// phpAdmin/db.php  –  Cau hinh ket noi MySQL
// =============================================================
// Cach dung:  require_once __DIR__ . '/db.php';
//             $pdo = getDB();
// =============================================================

// -------- CHINH SUA 4 DONG NAY CHO DUNG VOI MAY CUA BAN --------
define('DB_HOST', 'localhost');
define('DB_PORT', 3306);
define('DB_NAME', 'shop_hoa_db');   // ten database tren phpMyAdmin
define('DB_USER', 'root');          // XAMPP mac dinh: root
define('DB_PASS', '');              // XAMPP mac dinh: de trong
// ----------------------------------------------------------------

// Thu muc luu anh upload (../Image/ tinh tu vi tri file nay)
define('IMG_UPLOAD_DIR', realpath(__DIR__ . '/../ImageSanPham') . DIRECTORY_SEPARATOR);

/**
 * Tra ve ket noi PDO (singleton)
 */
function getDB(): PDO
{
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
        DB_HOST, DB_PORT, DB_NAME
    );

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'message' => 'Loi ket noi CSDL: ' . $e->getMessage(),
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    return $pdo;
}