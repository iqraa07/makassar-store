<?php
// ============================================================
// DASHBOARD — MAKASSASTORE POS
// ============================================================
require_once 'config/database.php';
$db = getDB();

// Stats hari ini
$today = date('Y-m-d');
$q = $db->query("SELECT COUNT(*) as total_trx, COALESCE(SUM(total_bayar),0) as omset FROM tbl_transaksi WHERE DATE(created_at)='$today' AND status='selesai'");
$todayStats = $q->fetch_assoc();

// Stats bulan ini
$month = date('Y-m');
$q2 = $db->query("SELECT COUNT(*) as total_trx, COALESCE(SUM(total_bayar),0) as omset FROM tbl_transaksi WHERE DATE_FORMAT(created_at,'%Y-%m')='$month' AND status='selesai'");
$monthStats = $q2->fetch_assoc();

// Total barang & member
$totalBarang = $db->query("SELECT COUNT(*) as c FROM tbl_barang")->fetch_assoc()['c'];
$totalMember = $db->query("SELECT COUNT(*) as c FROM tbl_member WHERE status='aktif'")->fetch_assoc()['c'];
$stokKritis  = $db->query("SELECT COUNT(*) as c FROM tbl_barang WHERE stok <= stok_minimum")->fetch_assoc()['c'];

// Transaksi 7 hari (chart)
$q3 = $db->query("
    SELECT DATE(created_at) as tgl, COALESCE(SUM(total_bayar),0) as total
    FROM tbl_transaksi
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) AND status='selesai'
    GROUP BY DATE(created_at)
    ORDER BY tgl ASC
");
$chartLabels = []; $chartData = [];
// Siapkan 7 hari
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $chartLabels[$d] = date('d/m', strtotime($d));
    $chartData[$d] = 0;
}
while ($row = $q3->fetch_assoc()) {
    if (isset($chartData[$row['tgl']])) $chartData[$row['tgl']] = (float)$row['total'];
}

// Barang terlaris
$topBarang = $db->query("
    SELECT b.nama_barang, SUM(dt.qty) as total_qty, SUM(dt.subtotal) as total_omset
    FROM tbl_detail_transaksi dt
    JOIN tbl_barang b ON dt.id_barang = b.id
    JOIN tbl_transaksi t ON dt.id_transaksi = t.id
    WHERE t.status = 'selesai'
    GROUP BY dt.id_barang
    ORDER BY total_qty DESC
    LIMIT 5
");

// Transaksi terbaru
$recentTrx = $db->query("
    SELECT t.*, m.nama as nama_member
    FROM tbl_transaksi t
    LEFT JOIN tbl_member m ON t.id_member = m.id
    ORDER BY t.created_at DESC
    LIMIT 8
");

// Stok kritis list
$stokKritisList = $db->query("
    SELECT b.*, k.nama_kategori
    FROM tbl_barang b
    LEFT JOIN tbl_kategori k ON b.id_kategori = k.id
    WHERE b.stok <= b.stok_minimum
    ORDER BY b.stok ASC
    LIMIT 6
");

$currentPage = 'dashboard';
$pageTitle   = 'Dashboard';
$pageSub     = 'Ringkasan hari ini — ' . date('l, d F Y');
include 'includes/header.php';
?>

<!-- Stats Grid -->
<div class="stats-grid">
    <!-- Omset Hari Ini -->
    <div class="stat-card amber">
        <div class="stat-icon"><i class="fa-solid fa-coins"></i></div>
        <div class="stat-label">Omset Hari Ini</div>
        <div class="stat-value" data-count="<?= $todayStats['omset'] ?>" data-prefix="Rp ">Rp 0</div>
        <div class="stat-sub">
            <span class="up"><i class="fa-solid fa-arrow-up"></i> <?= $todayStats['total_trx'] ?> transaksi</span> selesai
        </div>
    </div>

    <!-- Omset Bulan Ini -->
    <div class="stat-card indigo">
        <div class="stat-icon"><i class="fa-solid fa-chart-line"></i></div>
        <div class="stat-label">Omset Bulan Ini</div>
        <div class="stat-value" data-count="<?= $monthStats['omset'] ?>" data-prefix="Rp ">Rp 0</div>
        <div class="stat-sub"><?= $monthStats['total_trx'] ?> transaksi · <?= date('F Y') ?></div>
    </div>

    <!-- Total Barang -->
    <div class="stat-card green">
        <div class="stat-icon"><i class="fa-solid fa-box-open"></i></div>
        <div class="stat-label">Total Barang</div>
        <div class="stat-value" data-count="<?= $totalBarang ?>" data-suffix=" produk">0 produk</div>
        <div class="stat-sub">
            <?php if($stokKritis > 0): ?>
            <span class="down"><i class="fa-solid fa-triangle-exclamation"></i> <?= $stokKritis ?> stok kritis</span>
            <?php else: ?>
            <span class="up"><i class="fa-solid fa-check"></i> Semua stok aman</span>
            <?php endif; ?>
        </div>
    </div>

    <!-- Total Member -->
    <div class="stat-card blue">
        <div class="stat-icon"><i class="fa-solid fa-user-group"></i></div>
        <div class="stat-label">Member Aktif</div>
        <div class="stat-value" data-count="<?= $totalMember ?>" data-suffix=" member">0 member</div>
        <div class="stat-sub">Pelanggan terdaftar</div>
    </div>
</div>

<!-- Charts & Top Produk -->
<div class="grid-60-40" style="margin-bottom:20px">
    <!-- Chart Penjualan 7 Hari -->
    <div class="card">
        <div class="card-header">
            <i class="fa-solid fa-chart-area" style="color:var(--primary)"></i>
            <h3>Grafik Penjualan 7 Hari</h3>
            <span class="badge badge-primary">Real-time</span>
        </div>
        <div class="card-body">
            <div class="chart-container">
                <canvas id="chartPenjualan"></canvas>
            </div>
        </div>
    </div>

    <!-- Produk Terlaris -->
    <div class="card">
        <div class="card-header">
            <i class="fa-solid fa-fire" style="color:var(--amber)"></i>
            <h3>Produk Terlaris</h3>
        </div>
        <div class="card-body" style="padding:12px 16px">
            <?php $rank = 1; while ($row = $topBarang->fetch_assoc()): ?>
            <div style="display:flex;align-items:center;gap:12px;padding:10px 0;border-bottom:1px solid var(--border-light)">
                <div style="width:28px;height:28px;border-radius:50%;background:<?= ['var(--amber)','var(--primary)','var(--success)','var(--info)','var(--text-muted)'][$rank-1] ?>;color:white;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:800;flex-shrink:0"><?= $rank ?></div>
                <div style="flex:1;min-width:0">
                    <div style="font-size:13px;font-weight:600;color:var(--text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= htmlspecialchars($row['nama_barang']) ?></div>
                    <div style="font-size:11.5px;color:var(--text-muted)"><?= $row['total_qty'] ?> terjual</div>
                </div>
                <div style="font-size:13px;font-weight:700;color:var(--amber);text-align:right"><?= formatRupiah($row['total_omset']) ?></div>
            </div>
            <?php $rank++; endwhile; ?>
            <?php if($rank === 1): ?>
            <div class="empty-state" style="padding:30px">
                <i class="fa-solid fa-chart-bar"></i>
                <p>Belum ada data penjualan</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Recent Transactions & Stok Kritis -->
<div class="grid-60-40">
    <!-- Transaksi Terbaru -->
    <div class="card">
        <div class="card-header">
            <i class="fa-solid fa-receipt" style="color:var(--success)"></i>
            <h3>Transaksi Terbaru</h3>
            <a href="laporan.php" class="btn btn-ghost btn-sm">Lihat Semua</a>
        </div>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Member</th>
                        <th>Total</th>
                        <th>Metode</th>
                        <th>Waktu</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($trx = $recentTrx->fetch_assoc()): ?>
                    <tr>
                        <td><span class="td-code"><?= $trx['kode_transaksi'] ?></span></td>
                        <td class="td-primary"><?= $trx['nama_member'] ? htmlspecialchars($trx['nama_member']) : '<span class="text-muted">Umum</span>' ?></td>
                        <td class="td-primary"><?= formatRupiah($trx['total_bayar']) ?></td>
                        <td>
                            <?php
                            $metodeBadge = ['tunai'=>'badge-success','qris'=>'badge-primary','transfer'=>'badge-info'];
                            $metode = $trx['metode_bayar'];
                            ?>
                            <span class="badge <?= $metodeBadge[$metode] ?? 'badge-neutral' ?>"><?= ucfirst($metode) ?></span>
                        </td>
                        <td class="text-muted"><?= date('d/m H:i', strtotime($trx['created_at'])) ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Stok Kritis -->
    <div class="card">
        <div class="card-header">
            <i class="fa-solid fa-triangle-exclamation" style="color:var(--warning)"></i>
            <h3>Stok Kritis</h3>
            <a href="barang.php" class="btn btn-ghost btn-sm">Kelola</a>
        </div>
        <div class="card-body" style="padding:12px 16px">
            <?php $stokRows = []; while($s = $stokKritisList->fetch_assoc()) $stokRows[] = $s; ?>
            <?php if(empty($stokRows)): ?>
            <div style="text-align:center;padding:30px;color:var(--success)">
                <i class="fa-solid fa-circle-check" style="font-size:36px;display:block;margin-bottom:10px"></i>
                <p style="font-size:13px">Semua stok dalam kondisi aman!</p>
            </div>
            <?php else: ?>
            <?php foreach($stokRows as $s):
                $pct = $s['stok_minimum'] > 0 ? min(100, ($s['stok'] / ($s['stok_minimum'] * 3)) * 100) : 50;
                $stokClass = $s['stok'] == 0 ? 'danger' : ($s['stok'] <= $s['stok_minimum'] ? 'low' : '');
            ?>
            <div style="padding:10px 0;border-bottom:1px solid var(--border-light)">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:5px">
                    <span style="font-size:13px;font-weight:600;color:var(--text)"><?= htmlspecialchars($s['nama_barang']) ?></span>
                    <span class="badge <?= $s['stok'] == 0 ? 'badge-danger' : 'badge-warning' ?>">
                        <?= $s['stok'] == 0 ? 'HABIS' : $s['stok'] . ' ' . $s['satuan'] ?>
                    </span>
                </div>
                <div class="stok-bar">
                    <div class="stok-bar-fill">
                        <div class="stok-bar-val <?= $stokClass ?>" style="width:<?= max(4, $pct) ?>%"></div>
                    </div>
                    <span style="font-size:11px;color:var(--text-muted);white-space:nowrap">min <?= $s['stok_minimum'] ?></span>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// ─── Chart Penjualan ───
window.addEventListener('load', function() {
    const canvas = document.getElementById('chartPenjualan');
    if (!canvas || typeof Chart === 'undefined') {
        canvas && (canvas.parentElement.innerHTML = '<div class="empty-state" style="padding:30px"><i class="fa-solid fa-chart-line"></i><p>Chart tidak dapat dimuat.<br><small>Periksa koneksi internet.</small></p></div>');
        return;
    }
    const ctx = canvas.getContext('2d');
    const labels     = <?= json_encode(array_values($chartLabels)) ?>;
    const dataValues = <?= json_encode(array_values($chartData)) ?>;
    const hasData    = dataValues.some(v => v > 0);

    if (!hasData) {
        canvas.parentElement.innerHTML = '<div class="empty-state" style="padding:30px"><i class="fa-solid fa-chart-line"></i><p>Belum ada transaksi dalam 7 hari ini</p></div>';
        return;
    }

    const gradient = ctx.createLinearGradient(0, 0, 0, 240);
    gradient.addColorStop(0, 'rgba(99,102,241,0.35)');
    gradient.addColorStop(1, 'rgba(99,102,241,0.01)');

    new Chart(ctx, {
        type: 'line',
        data: {
            labels,
            datasets: [{
                label: 'Omset (Rp)',
                data: dataValues,
                borderColor: '#6366f1',
                backgroundColor: gradient,
                borderWidth: 2.5,
                pointBackgroundColor: '#6366f1',
                pointBorderColor: '#080c14',
                pointBorderWidth: 2,
                pointRadius: 5,
                pointHoverRadius: 7,
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#0f1825',
                    titleColor: '#f1f5f9',
                    bodyColor: '#94a3b8',
                    borderColor: 'rgba(255,255,255,0.08)',
                    borderWidth: 1,
                    padding: 12,
                    callbacks: { label: c => ' Rp ' + c.raw.toLocaleString('id-ID') }
                }
            },
            scales: {
                x: { grid: { color: 'rgba(255,255,255,0.04)' }, ticks: { color: '#475569', font: { size: 11 } } },
                y: {
                    grid: { color: 'rgba(255,255,255,0.04)' },
                    ticks: { color: '#475569', font: { size: 11 },
                        callback: v => 'Rp ' + (v >= 1000000 ? (v/1000000).toFixed(1)+'jt' : v >= 1000 ? (v/1000).toFixed(0)+'rb' : v)
                    },
                    beginAtZero: true
                }
            }
        }
    });
});
</script>

<?php include 'includes/footer.php'; ?>
