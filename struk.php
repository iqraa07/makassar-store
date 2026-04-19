<?php
// ============================================================
// STRUK CETAK — Print Receipt Page
// ============================================================
require_once 'config/database.php';
$db = getDB();

$id = (int)($_GET['id'] ?? 0);
if (!$id) { echo '<p style="text-align:center;padding:40px;color:red">ID transaksi tidak valid.</p>'; exit; }

$trx = $db->query("
    SELECT t.*, IFNULL(m.nama,'Umum') as nama_member, IFNULL(m.kode_member,'') as kode_member, IFNULL(m.poin,0) as poin_member
    FROM tbl_transaksi t
    LEFT JOIN tbl_member m ON t.id_member = m.id
    WHERE t.id = $id
")->fetch_assoc();

if (!$trx) { echo '<p style="text-align:center;padding:40px;color:red">Transaksi tidak ditemukan.</p>'; exit; }

$items = $db->query("SELECT * FROM tbl_detail_transaksi WHERE id_transaksi = $id ORDER BY id");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struk — <?= $trx['kode_transaksi'] ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: #0a0f1e;
            display: flex;
            min-height: 100vh;
            align-items: center;
            justify-content: center;
            padding: 30px;
        }

        .print-wrapper {
            display: flex;
            gap: 30px;
            align-items: flex-start;
            max-width: 800px;
            width: 100%;
        }

        .struk {
            background: white;
            width: 320px;
            flex-shrink: 0;
            border-radius: 4px;
            padding: 24px 20px;
            font-family: 'Courier New', Courier, monospace;
            font-size: 12.5px;
            color: #111;
            box-shadow: 0 8px 40px rgba(0,0,0,0.5);
            position: relative;
        }

        .struk::before, .struk::after {
            content: '';
            display: block;
            height: 15px;
            background: repeating-linear-gradient(90deg, white 0, white 8px, transparent 8px, transparent 14px);
        }

        .struk::before {
            background: repeating-linear-gradient(90deg, #0a0f1e 0, #0a0f1e 8px, white 8px, white 14px);
            margin: -24px -20px 16px;
            border-radius: 4px 4px 0 0;
        }

        .struk::after {
            margin: 16px -20px -24px;
            border-radius: 0 0 4px 4px;
            background: repeating-linear-gradient(90deg, #0a0f1e 0, #0a0f1e 8px, white 8px, white 14px);
        }

        .struk-brand {
            text-align: center;
            padding-bottom: 12px;
            border-bottom: 1px dashed #ccc;
            margin-bottom: 12px;
        }

        .struk-nama {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 20px;
            font-weight: 900;
            letter-spacing: 2px;
            color: #111;
            display: block;
        }

        .struk-tagline { font-size: 10.5px; color: #777; margin-top: 2px; }
        .struk-alamat  { font-size: 10px; color: #999; }

        .struk-info {
            font-size: 11px;
            line-height: 1.8;
            color: #555;
            margin-bottom: 12px;
        }

        .struk-info strong { color: #111; }

        .struk-divider {
            border: none;
            border-top: 1px dashed #ccc;
            margin: 10px 0;
        }

        .struk-item {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            padding: 5px 0;
        }

        .struk-item .nama { flex: 1; padding-right: 8px; line-height: 1.3; }
        .struk-item .total { font-weight: 700; white-space: nowrap; }
        .struk-item .harga-info { font-size: 10.5px; color: #777; }

        .struk-summary td {
            padding: 4px 0;
            font-size: 12px;
            color: #444;
            vertical-align: top;
        }

        .struk-summary td:last-child {
            text-align: right;
            font-weight: 600;
        }

        .struk-total-row td {
            font-size: 16px;
            font-weight: 900;
            color: #111;
            padding-top: 8px;
        }

        .struk-footer {
            text-align: center;
            margin-top: 12px;
            font-size: 10.5px;
            color: #999;
            line-height: 1.7;
        }

        .barcode {
            text-align: center;
            margin: 12px 0 6px;
            font-family: 'Courier New', monospace;
            font-size: 11px;
            letter-spacing: 3px;
            color: #333;
        }

        /* Info Panel */
        .info-panel {
            flex: 1;
            color: white;
        }

        .info-logo {
            font-size: 36px;
            margin-bottom: 10px;
        }

        .info-title {
            font-size: 22px;
            font-weight: 800;
            margin-bottom: 4px;
            color: #f1f5f9;
        }

        .info-sub {
            font-size: 13px;
            color: #64748b;
            margin-bottom: 24px;
        }

        .info-stat {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 12px;
        }

        .info-stat-label {
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #64748b;
            margin-bottom: 4px;
        }

        .info-stat-value {
            font-size: 22px;
            font-weight: 800;
            color: #f1f5f9;
        }

        .btn-print {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: linear-gradient(135deg, #6366f1, #818cf8);
            color: white;
            padding: 12px 24px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            border: none;
            box-shadow: 0 4px 14px rgba(99,102,241,0.4);
            margin-right: 10px;
            transition: all 0.2s;
        }

        .btn-print:hover { transform: translateY(-1px); }

        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(255,255,255,0.06);
            border: 1.5px solid rgba(255,255,255,0.1);
            color: #94a3b8;
            padding: 12px 20px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
        }

        .btn-back:hover { background: rgba(255,255,255,0.1); color: white; }

        @media print {
            body { background: white; padding: 0; }
            .info-panel { display: none; }
            .print-wrapper { display: block; max-width: 100%; }
            .struk { width: 100%; box-shadow: none; border-radius: 0; }
            .struk::before, .struk::after { display: none; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
<div class="print-wrapper">
    <!-- STRUK -->
    <div class="struk" id="struk">
        <div class="struk-brand">
            <span class="struk-nama">MAKASSAR STORE</span>
            <div class="struk-tagline">Belanja Mudah, Hidup Berkah — Khas Makassar</div>
            <div class="struk-alamat">Jl. Somba Opu No. 88, Makassar 90111</div>
            <div class="struk-alamat">Telp: (0411) 123-4567 | IG: @makassarstore</div>
        </div>

        <div class="struk-info">
            <strong>No. Struk :</strong> <?= $trx['kode_transaksi'] ?><br>
            <strong>Tanggal   :</strong> <?= date('d/m/Y H:i:s', strtotime($trx['created_at'])) ?><br>
            <strong>Kasir     :</strong> <?= htmlspecialchars($trx['kasir']) ?><br>
            <?php if($trx['nama_member'] !== 'Umum'): ?>
            <strong>Member    :</strong> <?= htmlspecialchars($trx['nama_member']) ?> (<?= $trx['kode_member'] ?>)
            <?php else: ?>
            <strong>Pelanggan :</strong> Umum (Non-Member)
            <?php endif; ?>
        </div>

        <hr class="struk-divider">

        <?php while($item = $items->fetch_assoc()): ?>
        <div class="struk-item">
            <div class="nama">
                <?= htmlspecialchars($item['nama_barang']) ?>
                <div class="harga-info"><?= $item['qty'] ?> × Rp <?= number_format($item['harga_satuan'],0,',','.') ?></div>
            </div>
            <div class="total">Rp <?= number_format($item['subtotal'],0,',','.') ?></div>
        </div>
        <?php endwhile; ?>

        <hr class="struk-divider">

        <table class="struk-summary" width="100%">
            <tr>
                <td>Subtotal</td>
                <td>Rp <?= number_format($trx['total_harga'],0,',','.') ?></td>
            </tr>
            <?php if($trx['diskon'] > 0): ?>
            <tr>
                <td>Diskon</td>
                <td style="color:#059669">- Rp <?= number_format($trx['diskon'],0,',','.') ?></td>
            </tr>
            <?php endif; ?>
            <tr class="struk-divider-tr">
                <td colspan="2"><hr class="struk-divider" style="margin:6px 0"></td>
            </tr>
            <tr class="struk-total-row">
                <td>TOTAL</td>
                <td>Rp <?= number_format($trx['total_bayar'],0,',','.') ?></td>
            </tr>
            <?php if($trx['uang_bayar'] > 0): ?>
            <tr>
                <td style="font-size:12px;color:#555">Dibayar (<?= ucfirst($trx['metode_bayar']) ?>)</td>
                <td style="font-size:12px;color:#555">Rp <?= number_format($trx['uang_bayar'],0,',','.') ?></td>
            </tr>
            <tr>
                <td style="font-size:13px;font-weight:700">Kembalian</td>
                <td style="font-size:13px;font-weight:700;color:#059669">Rp <?= number_format($trx['kembalian'],0,',','.') ?></td>
            </tr>
            <?php endif; ?>
        </table>

        <?php if($trx['nama_member'] !== 'Umum'): ?>
        <hr class="struk-divider">
        <div style="font-size:11px;color:#555;text-align:center">
            Poin Member: <strong><?= number_format($trx['poin_member']) ?></strong>
            <?php $poinDapat = (int)($trx['total_bayar'] / 1000); ?>
            (+<?= $poinDapat ?> poin dari transaksi ini)
        </div>
        <?php endif; ?>

        <?php if($trx['catatan']): ?>
        <hr class="struk-divider">
        <div style="font-size:11px;color:#666">Catatan: <?= htmlspecialchars($trx['catatan']) ?></div>
        <?php endif; ?>

        <div class="barcode">
            ||||| <?= $trx['kode_transaksi'] ?> |||||
        </div>

        <div class="struk-footer">
            ✓ Terima kasih telah berbelanja di Makassar Store!<br>
            Barang yang sudah dibeli tidak dapat dikembalikan.<br>
            Simpan struk ini sebagai bukti pembayaran.<br>
            <span style="font-size:9.5px;color:#bbb">Makassar Store — Khas Makassar 🏬</span>
        </div>
    </div>

    <!-- INFO PANEL -->
    <div class="info-panel no-print">
        <div class="info-logo">🏬</div>
        <div class="info-title">Transaksi Berhasil!</div>
        <div class="info-sub">Tersimpan di sistem Makassar Store</div>

        <div class="info-stat">
            <div class="info-stat-label">Kode Transaksi</div>
            <div class="info-stat-value" style="font-size:16px;font-family:monospace"><?= $trx['kode_transaksi'] ?></div>
        </div>

        <div class="info-stat">
            <div class="info-stat-label">Total Pembayaran</div>
            <div class="info-stat-value" style="color:#f59e0b">Rp <?= number_format($trx['total_bayar'],0,',','.') ?></div>
        </div>

        <?php if($trx['kembalian'] > 0): ?>
        <div class="info-stat" style="border-color:rgba(16,185,129,0.2)">
            <div class="info-stat-label">Kembalian</div>
            <div class="info-stat-value" style="color:#10b981">Rp <?= number_format($trx['kembalian'],0,',','.') ?></div>
        </div>
        <?php endif; ?>

        <div style="margin-top:20px">
            <button class="btn-print" onclick="window.print()">
                🖨️ Cetak Struk
            </button>
            <a href="transaksi.php" class="btn-back">
                ← Transaksi Baru
            </a>
        </div>

        <div style="margin-top:16px">
            <a href="laporan.php" style="color:#64748b;font-size:13px;text-decoration:underline">Lihat Laporan Penjualan</a>
        </div>
    </div>
</div>

<script>
// Auto print jika dibuka dari popup
if (window.opener || window.history.length === 1) {
    // Don't auto print, let user click
}
</script>
</body>
</html>
