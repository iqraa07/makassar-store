<?php
// ============================================================
// AUTH MIDDLEWARE — MakassaStore
// ============================================================
if (session_status() === PHP_SESSION_NONE) session_start();

function requireLogin() {
    if (empty($_SESSION['kasir_id'])) {
        header('Location: login.php');
        exit;
    }
}

function getCurrentUser() {
    return [
        'id'       => $_SESSION['kasir_id']   ?? 0,
        'nama'     => $_SESSION['kasir_nama']  ?? 'Admin',
        'username' => $_SESSION['kasir_user']  ?? 'admin',
        'role'     => $_SESSION['kasir_role']  ?? 'kasir',
        'avatar'   => strtoupper(substr($_SESSION['kasir_nama'] ?? 'A', 0, 1)),
    ];
}

function isAdmin() {
    return ($_SESSION['kasir_role'] ?? '') === 'admin';
}
