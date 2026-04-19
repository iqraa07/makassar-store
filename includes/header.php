<?php
// ============================================================
// Includes/Header — MakassaStore POS v3
// ============================================================
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/auth.php';
requireLogin();

$currentUser = getCurrentUser();
$currentPage = isset($currentPage) ? $currentPage : '';
$pageTitle   = isset($pageTitle)   ? $pageTitle   : 'Dashboard';
$pageSub     = isset($pageSub)     ? $pageSub     : 'Makassar Store';
$db = getDB();

// Stok kritis
$stokKritis = $db->query("SELECT COUNT(*) as c FROM tbl_barang WHERE stok <= stok_minimum")->fetch_assoc()['c'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Makassar Store — Sistem Kasir Modern Khas Makassar">
    <title><?= $pageTitle ?> — <?= APP_NAME ?></title>

    <!-- Fonts & Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800;900&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- App CSS -->
    <link rel="stylesheet" href="assets/css/style.css">

    <style>
    /* ── Page Transition ── */
    .page-content {
        animation: pageIn 0.35s cubic-bezier(0.4, 0, 0.2, 1) both;
    }
    @keyframes pageIn {
        from { opacity: 0; transform: translateY(12px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    /* ── Stat card count-up ── */
    .stat-value[data-count] { transition: none; }

    /* ── Skeleton loader ── */
    .skeleton {
        background: linear-gradient(90deg, var(--bg-card) 25%, var(--bg-hover) 50%, var(--bg-card) 75%);
        background-size: 400% 100%;
        animation: shimmer 1.4s ease infinite;
        border-radius: 8px;
    }
    @keyframes shimmer {
        0%   { background-position: 100% 50%; }
        100% { background-position: 0% 50%; }
    }

    /* ── Ripple on buttons ── */
    .btn { position: relative; overflow: hidden; }
    .ripple {
        position: absolute;
        border-radius: 50%;
        transform: scale(0);
        animation: rippleAnim 0.5s linear;
        background: rgba(255,255,255,0.25);
        pointer-events: none;
    }
    @keyframes rippleAnim {
        to { transform: scale(4); opacity: 0; }
    }

    /* ── Sidebar active glow ── */
    .nav-item.active {
        background: var(--primary-soft);
        color: var(--primary);
        font-weight: 600;
        box-shadow: inset 0 0 0 1px rgba(99,102,241,0.2);
    }

    /* ── Sidebar logo pulse ── */
    .brand-icon { animation: brandPulse 4s ease-in-out infinite; }
    @keyframes brandPulse {
        0%,100% { box-shadow: 0 4px 14px rgba(99,102,241,0.3); }
        50%      { box-shadow: 0 4px 24px rgba(99,102,241,0.55); }
    }

    /* ── User chip dropdown ── */
    .user-dropdown {
        position: relative;
    }
    .user-menu {
        position: absolute;
        top: calc(100% + 8px);
        right: 0;
        background: var(--bg-card);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        min-width: 180px;
        overflow: hidden;
        box-shadow: var(--shadow-lg);
        opacity: 0; visibility: hidden;
        transform: translateY(-8px);
        transition: all 0.2s ease;
        z-index: 200;
    }
    .user-dropdown.open .user-menu {
        opacity: 1; visibility: visible; transform: translateY(0);
    }
    .user-menu-item {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 11px 16px;
        font-size: 13px;
        color: var(--text-sec);
        transition: all 0.15s;
        cursor: pointer;
        text-decoration: none;
    }
    .user-menu-item:hover { background: var(--bg-hover); color: var(--text); }
    .user-menu-item.danger { color: var(--danger); }
    .user-menu-item.danger:hover { background: var(--danger-soft); }
    .user-menu-divider { height: 1px; background: var(--border-light); margin: 4px 0; }

    /* ── Animate table rows ── */
    table.data-table tbody tr {
        animation: rowIn 0.3s ease both;
    }
    @keyframes rowIn {
        from { opacity:0; transform: translateX(-8px); }
        to   { opacity:1; transform: translateX(0); }
    }
    table.data-table tbody tr:nth-child(1)  { animation-delay: 0.02s; }
    table.data-table tbody tr:nth-child(2)  { animation-delay: 0.04s; }
    table.data-table tbody tr:nth-child(3)  { animation-delay: 0.06s; }
    table.data-table tbody tr:nth-child(4)  { animation-delay: 0.08s; }
    table.data-table tbody tr:nth-child(5)  { animation-delay: 0.10s; }
    table.data-table tbody tr:nth-child(6)  { animation-delay: 0.12s; }
    table.data-table tbody tr:nth-child(7)  { animation-delay: 0.14s; }
    table.data-table tbody tr:nth-child(8)  { animation-delay: 0.16s; }

    /* ── Stat cards animate in ── */
    .stat-card {
        animation: cardIn 0.4s cubic-bezier(0.34, 1.56, 0.64, 1) both;
    }
    .stat-card:nth-child(1) { animation-delay: 0.05s; }
    .stat-card:nth-child(2) { animation-delay: 0.10s; }
    .stat-card:nth-child(3) { animation-delay: 0.15s; }
    .stat-card:nth-child(4) { animation-delay: 0.20s; }
    @keyframes cardIn {
        from { opacity:0; transform: translateY(20px) scale(0.95); }
        to   { opacity:1; transform: translateY(0) scale(1); }
    }
    </style>
</head>
<body>
<div class="app-layout">

<!-- ═══ SIDEBAR ═══ -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="brand">
            <div class="brand-icon">🏬</div>
            <div class="brand-text">
                <div class="app-name"><?= APP_NAME ?></div>
                <div class="app-tag">Point of Sale System</div>
            </div>
        </div>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-section-label">Menu Utama</div>

        <a href="dashboard.php" class="nav-item <?= $currentPage === 'dashboard' ? 'active' : '' ?>">
            <span class="nav-icon"><i class="fa-solid fa-chart-pie"></i></span>
            Dashboard
        </a>

        <a href="transaksi.php" class="nav-item <?= $currentPage === 'transaksi' ? 'active' : '' ?>">
            <span class="nav-icon"><i class="fa-solid fa-cash-register"></i></span>
            Transaksi Kasir
        </a>

        <div class="nav-section-label" style="margin-top:6px">Manajemen</div>

        <a href="barang.php" class="nav-item <?= $currentPage === 'barang' ? 'active' : '' ?>">
            <span class="nav-icon"><i class="fa-solid fa-box"></i></span>
            Stok Barang
            <?php if($stokKritis > 0 && $currentPage !== 'barang'): ?>
            <span class="nav-badge"><?= $stokKritis ?></span>
            <?php endif; ?>
        </a>

        <a href="kategori.php" class="nav-item <?= $currentPage === 'kategori' ? 'active' : '' ?>">
            <span class="nav-icon"><i class="fa-solid fa-tags"></i></span>
            Kategori
        </a>

        <a href="member.php" class="nav-item <?= $currentPage === 'member' ? 'active' : '' ?>">
            <span class="nav-icon"><i class="fa-solid fa-users"></i></span>
            Member
        </a>

        <div class="nav-section-label" style="margin-top:6px">Laporan</div>

        <a href="laporan.php" class="nav-item <?= $currentPage === 'laporan' ? 'active' : '' ?>">
            <span class="nav-icon"><i class="fa-solid fa-file-chart-column"></i></span>
            Laporan Penjualan
        </a>

        <div class="nav-section-label" style="margin-top:6px">Akun</div>

        <a href="profil.php" class="nav-item <?= $currentPage === 'profil' ? 'active' : '' ?>">
            <span class="nav-icon"><i class="fa-solid fa-user-gear"></i></span>
            Profil & Setelan
        </a>
    </nav>

    <div class="sidebar-footer">
        <!-- User info di sidebar -->
        <a href="profil.php" style="display:flex;align-items:center;gap:10px;padding:10px;background:var(--bg-hover);border-radius:10px;margin-bottom:10px;text-decoration:none;transition:all 0.2s ease" onmouseover="this.style.background='var(--primary-soft)'" onmouseout="this.style.background='var(--bg-hover)'">
            <div style="width:34px;height:34px;border-radius:50%;background:linear-gradient(135deg,var(--primary),#a78bfa);display:flex;align-items:center;justify-content:center;font-size:14px;font-weight:700;color:white;flex-shrink:0">
                <?= $currentUser['avatar'] ?>
            </div>
            <div style="flex:1;min-width:0">
                <div style="font-size:13px;font-weight:600;color:var(--text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= htmlspecialchars($currentUser['nama']) ?></div>
                <div style="font-size:11px;color:var(--text-muted)"><?= ucfirst($currentUser['role']) ?> · <span style="color:var(--primary)">Edit Profil</span></div>
            </div>
            <i class="fa-solid fa-chevron-right" style="font-size:10px;color:var(--text-muted);flex-shrink:0"></i>
        </a>
        <!-- Clock Widget -->
        <div class="sidebar-time">
            <div style="display:flex;align-items:center;justify-content:center;gap:8px;margin-bottom:4px">
                <i class="fa-regular fa-clock" id="clock-icon" style="font-size:14px;color:var(--primary);animation:clockPulse 1s ease-in-out infinite"></i>
                <div class="time-val" id="live-time" style="font-variant-numeric:tabular-nums">--:--:--</div>
            </div>
            <div class="date-val" id="live-date">Loading...</div>
        </div>
    </div>
</aside>

<style>
@keyframes clockPulse {
    0%, 100% { opacity: 1; transform: scale(1); }
    50%       { opacity: 0.55; transform: scale(0.9); }
}
</style>

<!-- ═══ MAIN CONTENT ═══ -->
<div class="main-content">
    <!-- Topbar -->
    <header class="topbar">
        <button class="topbar-btn" onclick="toggleSidebar()" id="menu-btn" title="Toggle Sidebar">
            <i class="fa-solid fa-bars"></i>
        </button>
        <div class="topbar-title">
            <h1><?= $pageTitle ?></h1>
            <div class="breadcrumb"><?= APP_NAME ?> · <?= $pageSub ?></div>
        </div>
        <div class="topbar-actions">
            <?php if($stokKritis > 0): ?>
            <a href="barang.php?filter=kritis" class="topbar-btn" style="color:var(--warning);position:relative;" title="<?= $stokKritis ?> barang stok kritis">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <span style="position:absolute;top:-4px;right:-4px;background:var(--danger);color:white;font-size:9px;font-weight:700;width:16px;height:16px;border-radius:50%;display:flex;align-items:center;justify-content:center;"><?= $stokKritis ?></span>
            </a>
            <?php endif; ?>
            <a href="transaksi.php" class="btn btn-primary btn-sm" style="height:32px"><i class="fa-solid fa-plus"></i> Transaksi</a>
            <!-- User dropdown -->
            <div class="user-dropdown" id="userDropdown">
                <div class="user-chip" onclick="toggleUserMenu()">
                    <div class="user-avatar"><?= $currentUser['avatar'] ?></div>
                    <span><?= htmlspecialchars($currentUser['nama']) ?></span>
                    <i class="fa-solid fa-angle-down" style="font-size:10px;color:var(--text-muted)"></i>
                </div>
                <div class="user-menu">
                    <div style="padding:12px 16px 10px;border-bottom:1px solid var(--border-light)">
                        <div style="font-size:13px;font-weight:700;color:var(--text)"><?= htmlspecialchars($currentUser['nama']) ?></div>
                        <div style="font-size:11px;color:var(--text-muted)">@<?= htmlspecialchars($currentUser['username']) ?> · <?= ucfirst($currentUser['role']) ?></div>
                    </div>
                    <a href="dashboard.php" class="user-menu-item"><i class="fa-solid fa-gauge"></i> Dashboard</a>
                    <a href="profil.php" class="user-menu-item"><i class="fa-solid fa-user-pen"></i> Edit Profil</a>
                    <a href="profil.php?tab=password" class="user-menu-item"><i class="fa-solid fa-lock"></i> Ganti Password</a>
                    <div class="user-menu-divider"></div>
                    <a href="logout.php" class="user-menu-item danger"><i class="fa-solid fa-right-from-bracket"></i> Keluar</a>
                </div>
            </div>
        </div>
    </header>

    <!-- Toast container -->
    <div class="toast-container" id="toast-container"></div>

    <!-- Page Content -->
    <div class="page-content">
