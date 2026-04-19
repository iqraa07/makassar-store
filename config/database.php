<?php
// ============================================================
// MAKASSAR STORE — Konfigurasi Database & Konstan Aplikasi
// ============================================================

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'makassar_store');
define('DB_PORT', 3306);

define('APP_NAME', 'Makassar Store');
define('APP_TAGLINE', 'Belanja Mudah, Hidup Berkah — Khas Makassar');
define('APP_VERSION', '3.0');
define('KASIR_NAME', 'Admin');
define('REGISTER_CODE', 'MKSTR2026'); // kode buat daftar akun baru

function getDB()
{
    static $conn = null;
    if ($conn === null) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
        if ($conn->connect_error) {
            http_response_code(500);
            die(json_encode([
                'success' => false,
                'message' => 'Koneksi database gagal: ' . $conn->connect_error
            ]));
        }
        $conn->set_charset('utf8mb4');
    }
    return $conn;
}

function formatRupiah($angka)
{
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

function generateKode($prefix, $table, $kolom)
{
    $db = getDB();
    $date = date('Ymd');
    $like = $prefix . $date . '%';
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM $table WHERE $kolom LIKE ?");
    $stmt->bind_param('s', $like);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $no = str_pad($result['total'] + 1, 3, '0', STR_PAD_LEFT);
    return $prefix . $date . $no;
}

function sanitize($data)
{
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

function jsonResponse($data)
{
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// Auto-create tabel users jika belum ada
function ensureUsersTable()
{
    $db = getDB();
    $db->query("CREATE TABLE IF NOT EXISTS tbl_users (
        id          INT AUTO_INCREMENT PRIMARY KEY,
        nama        VARCHAR(100) NOT NULL,
        username    VARCHAR(50)  NOT NULL UNIQUE,
        email       VARCHAR(100) DEFAULT NULL,
        password    VARCHAR(255) NOT NULL,
        role        ENUM('admin','kasir') DEFAULT 'kasir',
        status      ENUM('aktif','nonaktif') DEFAULT 'aktif',
        last_login  DATETIME DEFAULT NULL,
        created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");

    // Default admin jika tabel kosong
    $count = $db->query("SELECT COUNT(*) as c FROM tbl_users")->fetch_assoc()['c'];
    if ($count == 0) {
        $hash = password_hash('admin123', PASSWORD_BCRYPT);
        $db->query("INSERT INTO tbl_users(nama,username,email,password,role) VALUES('Administrator','admin','admin@makassarstore.id','$hash','admin')");
    }
}
ensureUsersTable();
