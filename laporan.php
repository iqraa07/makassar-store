<?php
// ============================================================
// LAPORAN PENJUALAN — Dengan Filter & Export Excel
// ============================================================
require_once 'config/database.php';
$db = getDB();

// Export to Excel
if (isset($_GET['export']) && $_GET['export'] === 'excel') {
    $tglDari   = $_GET['dari']   ?? date('Y-m-01');
    $tglSampai = $_GET['sampai'] ?? date('Y-m-d');
    $metode    = $_GET['metode'] ?? '';
    $memberId  = (int)($_GET['member'] ?? 0);

    $where = ["DATE(t.created_at) BETWEEN '$tglDari' AND '$tglSampai'", "t.status='selesai'"];
    if ($metode && in_array($metode, ['tunai','qris','transfer'])) $where[] = "t.metode_bayar='$metode'";
    if ($memberId) $where[] = "t.id_member=$memberId";
    $ws = implode(' AND ', $where);

    $result = $db->query("
        SELECT t.kode_transaksi, IFNULL(m.nama,'Umum') AS member,
               t.total_harga, t.diskon, t.total_bayar, t.metode_bayar,
               t.kasir, t.created_at,
               GROUP_CONCAT(dt.nama_barang, ' (', dt.qty, ')' ORDER BY dt.id SEPARATOR '; ') AS items
        FROM tbl_transaksi t
        LEFT JOIN tbl_member m ON t.id_member = m.id
        LEFT JOIN tbl_detail_transaksi dt ON t.id = dt.id_transaksi
        WHERE $ws GROUP BY t.id ORDER BY t.created_at DESC
    ");

    // Collect data & summary
    $rows = [];
    $totalOmset = 0; $totalDiskon = 0; $totalTrx = 0;
    while ($r = $result->fetch_assoc()) {
        $rows[] = $r;
        $totalOmset  += $r['total_bayar'];
        $totalDiskon += $r['diskon'];
        $totalTrx++;
    }

    $periodeLabel = date('d M Y', strtotime($tglDari)) . ' s/d ' . date('d M Y', strtotime($tglSampai));
    $metodeLabel  = $metode ? ucfirst($metode) : 'Semua';

    header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
    header('Content-Disposition: attachment; filename="Laporan_Penjualan_' . date('Ymd') . '.xls"');
    header('Cache-Control: max-age=0');

    echo '<html xmlns:o="urn:schemas-microsoft-com:office:office"
               xmlns:x="urn:schemas-microsoft-com:office:excel"
               xmlns="http://www.w3.org/TR/REC-html40">';
    echo '<head><meta charset="UTF-8">';
    echo '<style>
        body { font-family: Calibri, Arial, sans-serif; font-size: 11pt; }
        td, th { border: 1px solid #b0c4de; padding: 5px 8px; white-space: nowrap; vertical-align: middle; }
        .th-main { background-color: #1e3a5f; color: #ffffff; font-weight: bold; font-size: 12pt; text-align: center; }
        .th-sub  { background-color: #2d6a9f; color: #ffffff; font-size: 10pt; text-align: center; }
        .th-head { background-color: #1e3a5f; color: #ffffff; font-weight: bold; font-size: 10pt; text-align: center; }
        .td-no   { background-color: #f0f4f8; text-align: center; font-weight: bold; color: #1e3a5f; }
        .td-kode { font-family: Courier New, monospace; color: #1e3a5f; font-weight: bold; }
        .td-member { color: #155724; font-weight: 600; }
        .td-items { color: #4a4a4a; white-space: normal; }
        .td-num  { text-align: right; mso-number-format:"\#\,\#\#0"; }
        .td-total{ text-align: right; font-weight: bold; color: #1e3a5f; mso-number-format:"\#\,\#\#0"; }
        .td-diskon { text-align: right; color: #c0392b; mso-number-format:"\#\,\#\#0"; }
        .td-metode { text-align: center; }
        .badge-tunai    { background:#d4edda; color:#155724; padding:2px 7px; border-radius:4px; }
        .badge-qris     { background:#cce5ff; color:#004085; padding:2px 7px; border-radius:4px; }
        .badge-transfer { background:#fff3cd; color:#856404; padding:2px 7px; border-radius:4px; }
        .td-kasir { text-align: center; color: #555; }
        .td-tgl  { text-align: center; color: #333; }
        .row-odd  { background-color: #f8fafc; }
        .row-even { background-color: #ffffff; }
        .summary-label { font-weight: bold; color: #1e3a5f; text-align: right; background:#eaf0fb; }
        .summary-value { font-weight: bold; color: #1e3a5f; text-align: right; background:#eaf0fb; mso-number-format:"\#\,\#\#0"; }
    </style></head><body>';

    echo '<table style="border-collapse:collapse;width:100%">';

    echo '<tr><td colspan="10" class="th-main" style="font-size:15pt;letter-spacing:1px;padding:12px 8px">📊 LAPORAN PENJUALAN — MAKASSAR STORE</td></tr>';
    echo '<tr><td colspan="10" class="th-sub" style="font-size:10pt;padding:5px 8px">Periode: ' . $periodeLabel . ' &nbsp;|&nbsp; Metode: ' . $metodeLabel . ' &nbsp;|&nbsp; Dicetak: ' . date('d M Y, H:i') . ' WIB</td></tr>';
    echo '<tr><td colspan="10" style="height:8px;border:none;background:#fff"></td></tr>';

    echo '<tr>';
    echo '  <td colspan="3" class="summary-label" style="background:#eaf0fb;border:1px solid #b0c4de">Total Transaksi</td>';
    echo '  <td colspan="2" class="summary-value" style="background:#eaf0fb;border:1px solid #b0c4de">' . number_format($totalTrx) . ' transaksi</td>';
    echo '  <td colspan="2" class="summary-label" style="background:#eaf0fb;border:1px solid #b0c4de">Total Diskon</td>';
    echo '  <td colspan="3" class="summary-value" style="background:#eaf0fb;border:1px solid #b0c4de">Rp ' . number_format($totalDiskon, 0, ',', '.') . '</td>';
    echo '</tr>';
    echo '<tr>';
    echo '  <td colspan="3" class="summary-label" style="background:#eaf0fb;border:1px solid #b0c4de">Total Omset Bersih</td>';
    echo '  <td colspan="7" class="summary-value" style="background:#eaf0fb;border:1px solid #b0c4de;font-size:13pt;color:#1e3a5f">Rp ' . number_format($totalOmset, 0, ',', '.') . '</td>';
    echo '</tr>';
    echo '<tr><td colspan="10" style="height:8px;border:none;background:#fff"></td></tr>';

    echo '<tr>';
    $headers = ['No','Kode Transaksi','Member','Detail Item','Subtotal (Rp)','Diskon (Rp)','Total Bayar (Rp)','Metode','Kasir','Tanggal & Jam'];
    foreach ($headers as $h) {
        echo '<th class="th-head" style="padding:8px 6px">' . $h . '</th>';
    }
    echo '</tr>';

    $no = 1;
    foreach ($rows as $r) {
        $rowClass = ($no % 2 === 0) ? 'row-even' : 'row-odd';
        $badgeClass = 'badge-' . ($r['metode_bayar'] ?? 'tunai');
        echo '<tr class="' . $rowClass . '">';
        echo '  <td class="td-no">' . $no . '</td>';
        echo '  <td class="td-kode">' . htmlspecialchars($r['kode_transaksi']) . '</td>';
        echo '  <td class="td-member">' . htmlspecialchars($r['member']) . '</td>';
        echo '  <td class="td-items">' . htmlspecialchars($r['items'] ?? '-') . '</td>';
        echo '  <td class="td-num">' . number_format($r['total_harga'], 0, ',', '.') . '</td>';
        echo '  <td class="td-diskon">' . ($r['diskon'] > 0 ? number_format($r['diskon'], 0, ',', '.') : '-') . '</td>';
        echo '  <td class="td-total">' . number_format($r['total_bayar'], 0, ',', '.') . '</td>';
        echo '  <td class="td-metode"><span class="' . $badgeClass . '">' . ucfirst($r['metode_bayar']) . '</span></td>';
        echo '  <td class="td-kasir">' . htmlspecialchars($r['kasir']) . '</td>';
        echo '  <td class="td-tgl">' . date('d/m/Y  H:i', strtotime($r['created_at'])) . '</td>';
        echo '</tr>';
        $no++;
    }

    if (empty($rows)) {
        echo '<tr><td colspan="10" style="text-align:center;color:#888;padding:20px">Tidak ada data untuk periode ini.</td></tr>';
    }

    echo '<tr><td colspan="10" style="height:6px;border:none;background:#fff"></td></tr>';
    echo '<tr>';
    echo '  <td colspan="4" class="summary-label" style="border:1px solid #b0c4de">TOTAL KESELURUHAN</td>';
    echo '  <td class="summary-value" style="border:1px solid #b0c4de">Rp ' . number_format(array_sum(array_column($rows, 'total_harga')), 0, ',', '.') . '</td>';
    echo '  <td class="td-diskon summary-value" style="border:1px solid #b0c4de">Rp ' . number_format($totalDiskon, 0, ',', '.') . '</td>';
    echo '  <td class="td-total" style="border:1px solid #b0c4de;font-size:12pt">Rp ' . number_format($totalOmset, 0, ',', '.') . '</td>';
    echo '  <td colspan="3" style="border:1px solid #b0c4de;text-align:center;color:#555">— ' . $totalTrx . ' transaksi —</td>';
    echo '</tr>';

    echo '</table></body></html>';
    exit;
}

// Filters
$dari     = $_GET['dari']    ?? date('Y-m-01');
$sampai   = $_GET['sampai']  ?? date('Y-m-d');
$metode   = $_GET['metode']  ?? '';
$memberId = (int)($_GET['member'] ?? 0);

$where = ["DATE(t.created_at) BETWEEN '$dari' AND '$sampai'", "t.status='selesai'"];
if ($metode && in_array($metode,['tunai','qris','transfer'])) $where[] = "t.metode_bayar='$metode'";
if ($memberId) $where[] = "t.id_member=$memberId";
$whereStr = implode(' AND ',$where);

// Summary stats
$stats = $db->query("SELECT COUNT(*) as total_trx, COALESCE(SUM(total_bayar),0) as omset, COALESCE(SUM(diskon),0) as total_diskon, COALESCE(AVG(total_bayar),0) as avg_trx FROM tbl_transaksi t WHERE $whereStr")->fetch_assoc();

// By Method
$byMetode = $db->query("SELECT metode_bayar, COUNT(*) as jml, SUM(total_bayar) as total FROM tbl_transaksi t WHERE $whereStr GROUP BY metode_bayar");

// Transactions
$transaksiList = $db->query("
    SELECT t.*, IFNULL(m.nama,'Umum') as nama_member,
    (SELECT COUNT(*) FROM tbl_detail_transaksi WHERE id_transaksi=t.id) as item_count
    FROM tbl_transaksi t
    LEFT JOIN tbl_member m ON t.id_member = m.id
    WHERE $whereStr
    ORDER BY t.created_at DESC
");

// Chart by day
$chartQ = $db->query("
    SELECT DATE(t.created_at) as tgl, SUM(t.total_bayar) as total
    FROM tbl_transaksi t
    WHERE $whereStr
    GROUP BY DATE(t.created_at) ORDER BY tgl ASC
");
$cLabels = []; $cData = [];
while ($row = $chartQ->fetch_assoc()) {
    $cLabels[] = date('d/m', strtotime($row['tgl']));
    $cData[]   = (float)$row['total'];
}

// Member list for filter
$memberList = $db->query("SELECT id, nama FROM tbl_member WHERE status='aktif' ORDER BY nama");

$currentPage = 'laporan';
$pageTitle   = 'Laporan Penjualan';
$pageSub     = 'Analitik & Rekap Transaksi';
include 'includes/header.php';
?>

<!-- Filter Bar -->
<div class="card" style="margin-bottom:18px">
    <div class="filter-bar">
        <div class="form-group" style="margin:0">
            <label class="form-label" style="margin-bottom:4px">Dari Tanggal</label>
            <input type="date" id="f_dari" class="form-control" value="<?= $dari ?>">
        </div>
        <div class="form-group" style="margin:0">
            <label class="form-label" style="margin-bottom:4px">Sampai Tanggal</label>
            <input type="date" id="f_sampai" class="form-control" value="<?= $sampai ?>">
        </div>
        <div class="form-group" style="margin:0">
            <label class="form-label" style="margin-bottom:4px">Metode Bayar</label>
            <select id="f_metode" class="form-control">
                <option value="">Semua Metode</option>
                <option value="tunai" <?= $metode==='tunai'?'selected':'' ?>>Tunai</option>
                <option value="qris" <?= $metode==='qris'?'selected':'' ?>>QRIS</option>
                <option value="transfer" <?= $metode==='transfer'?'selected':'' ?>>Transfer</option>
            </select>
        </div>
        <div class="form-group" style="margin:0">
            <label class="form-label" style="margin-bottom:4px">Member</label>
            <select id="f_member" class="form-control" style="min-width:160px">
                <option value="">Semua Member</option>
                <?php while($m = $memberList->fetch_assoc()): ?>
                <option value="<?= $m['id'] ?>" <?= $memberId==$m['id']?'selected':'' ?>><?= htmlspecialchars($m['nama']) ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        <div style="display:flex;gap:8px;align-self:flex-end">
            <button class="btn btn-primary" onclick="applyFilter()"><i class="fa-solid fa-filter"></i> Filter</button>
            <button class="btn btn-ghost" onclick="resetFilter()"><i class="fa-solid fa-rotate-left"></i></button>
            <a href="#" onclick="exportExcel()" class="btn btn-success"><i class="fa-solid fa-file-excel"></i> Export Excel</a>
        </div>
    </div>
    <div style="padding:10px 18px;background:var(--bg-card-2);border-top:1px solid var(--border-light);font-size:12.5px;color:var(--text-muted)">
        <i class="fa-solid fa-calendar-days" style="margin-right:6px"></i>
        Periode: <strong style="color:var(--text)"><?= date('d M Y', strtotime($dari)) ?></strong> sampai <strong style="color:var(--text)"><?= date('d M Y', strtotime($sampai)) ?></strong>
        <?= $metode ? " · Metode: <strong style='color:var(--text)'>".ucfirst($metode)."</strong>" : '' ?>
    </div>
</div>

<!-- Summary Cards -->
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:20px">
    <div class="card" style="border-color:rgba(245,158,11,0.2)">
        <div class="card-body">
            <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:var(--text-muted);margin-bottom:8px">Total Omset</div>
            <div style="font-size:24px;font-weight:800;color:var(--amber)"><?= formatRupiah($stats['omset']) ?></div>
        </div>
    </div>
    <div class="card" style="border-color:rgba(99,102,241,0.2)">
        <div class="card-body">
            <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:var(--text-muted);margin-bottom:8px">Total Transaksi</div>
            <div style="font-size:24px;font-weight:800;color:var(--primary)"><?= number_format($stats['total_trx']) ?></div>
        </div>
    </div>
    <div class="card" style="border-color:rgba(16,185,129,0.2)">
        <div class="card-body">
            <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:var(--text-muted);margin-bottom:8px">Rata-rata / Transaksi</div>
            <div style="font-size:20px;font-weight:800;color:var(--success)"><?= formatRupiah($stats['avg_trx']) ?></div>
        </div>
    </div>
    <div class="card" style="border-color:rgba(239,68,68,0.2)">
        <div class="card-body">
            <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:var(--text-muted);margin-bottom:8px">Total Diskon</div>
            <div style="font-size:20px;font-weight:800;color:var(--danger)"><?= formatRupiah($stats['total_diskon']) ?></div>
        </div>
    </div>
</div>

<!-- Chart & Metode -->
<div class="grid-60-40" style="margin-bottom:20px">
    <div class="card">
        <div class="card-header">
            <i class="fa-solid fa-chart-column" style="color:var(--primary)"></i>
            <h3>Grafik Penjualan Periode</h3>
        </div>
        <div class="card-body">
            <div class="chart-container">
                <?php if(empty($cLabels)): ?>
                <div class="empty-state" style="padding:30px"><i class="fa-solid fa-chart-bar"></i><p>Tidak ada data untuk periode ini</p></div>
                <?php else: ?>
                <canvas id="chartLaporan"></canvas>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <i class="fa-solid fa-pie-chart" style="color:var(--amber)"></i>
            <h3>Rekap per Metode</h3>
        </div>
        <div class="card-body">
            <?php
            $metodeRows = [];
            while ($r = $byMetode->fetch_assoc()) $metodeRows[] = $r;
            $metodeIcons = ['tunai'=>'💵','qris'=>'📱','transfer'=>'🏦'];
            $metodeBadge = ['tunai'=>'badge-success','qris'=>'badge-primary','transfer'=>'badge-info'];
            foreach ($metodeRows as $r):
            ?>
            <div style="padding:12px 0;border-bottom:1px solid var(--border-light)">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px">
                    <div style="display:flex;align-items:center;gap:8px">
                        <span style="font-size:20px"><?= $metodeIcons[$r['metode_bayar']] ?? '💳' ?></span>
                        <div>
                            <div style="font-weight:600;color:var(--text)"><?= ucfirst($r['metode_bayar']) ?></div>
                            <div style="font-size:12px;color:var(--text-muted)"><?= $r['jml'] ?> transaksi</div>
                        </div>
                    </div>
                    <div style="font-weight:700;color:var(--amber)"><?= formatRupiah($r['total']) ?></div>
                </div>
                <?php if($stats['omset'] > 0):
                    $pct = ($r['total'] / $stats['omset']) * 100;
                ?>
                <div class="stok-bar-fill" style="width:100%;height:5px">
                    <div class="stok-bar-val" style="width:<?= number_format($pct,1) ?>%;background:var(--primary)"></div>
                </div>
                <div style="font-size:11px;color:var(--text-muted);margin-top:3px"><?= number_format($pct,1) ?>% dari total omset</div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
            <?php if(empty($metodeRows)): ?>
            <div class="empty-state" style="padding:30px"><i class="fa-solid fa-credit-card"></i><p>Tidak ada data</p></div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Tabel Transaksi -->
<div class="card">
    <div class="card-header">
        <i class="fa-solid fa-list" style="color:var(--success)"></i>
        <h3>Detail Transaksi</h3>
        <span class="badge badge-neutral"><?= $stats['total_trx'] ?> transaksi</span>
    </div>
    <div class="table-wrap">
        <table class="data-table" id="tabelLaporan">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Kode Transaksi</th>
                    <th>Member</th>
                    <th>Items</th>
                    <th>Subtotal</th>
                    <th>Diskon</th>
                    <th>Total</th>
                    <th>Metode</th>
                    <th>Tanggal</th>
                    <th width="70">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php $no=1; while($t = $transaksiList->fetch_assoc()): ?>
                <tr>
                    <td class="text-muted"><?= $no++ ?></td>
                    <td><span class="td-code"><?= $t['kode_transaksi'] ?></span></td>
                    <td class="td-primary"><?= $t['nama_member'] !== 'Umum' ? htmlspecialchars($t['nama_member']) : '<span class="text-muted">Umum</span>' ?></td>
                    <td class="text-muted"><?= $t['item_count'] ?> item</td>
                    <td><?= formatRupiah($t['total_harga']) ?></td>
                    <td><?= $t['diskon'] > 0 ? '<span class="text-success">- '.formatRupiah($t['diskon']).'</span>' : '-' ?></td>
                    <td class="td-primary"><?= formatRupiah($t['total_bayar']) ?></td>
                    <td>
                        <?php $mb = ['tunai'=>'badge-success','qris'=>'badge-primary','transfer'=>'badge-info']; ?>
                        <span class="badge <?= $mb[$t['metode_bayar']] ?? 'badge-neutral' ?>"><?= ucfirst($t['metode_bayar']) ?></span>
                    </td>
                    <td class="text-muted"><?= date('d/m/Y H:i', strtotime($t['created_at'])) ?></td>
                    <td>
                        <a href="struk.php?id=<?= $t['id'] ?>" target="_blank" class="btn btn-icon btn-ghost" title="Lihat Struk">
                            <i class="fa-solid fa-receipt"></i>
                        </a>
                    </td>
                </tr>
                <?php endwhile; ?>
                <?php if($no === 1): ?>
                <tr><td colspan="10" class="empty-state" style="padding:40px;text-align:center"><i class="fa-solid fa-receipt" style="font-size:36px;display:block;margin-bottom:10px;opacity:0.2"></i>Tidak ada transaksi untuk periode ini</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function applyFilter() {
    const dari    = document.getElementById('f_dari').value;
    const sampai  = document.getElementById('f_sampai').value;
    const metode  = document.getElementById('f_metode').value;
    const member  = document.getElementById('f_member').value;
    window.location.href = `laporan.php?dari=${dari}&sampai=${sampai}&metode=${metode}&member=${member}`;
}

function resetFilter() {
    document.getElementById('f_dari').value   = new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().slice(0,10);
    document.getElementById('f_sampai').value = new Date().toISOString().slice(0,10);
    document.getElementById('f_metode').value = '';
    document.getElementById('f_member').value = '';
    applyFilter();
}

function exportExcel() {
    const dari   = document.getElementById('f_dari').value;
    const sampai = document.getElementById('f_sampai').value;
    const metode = document.getElementById('f_metode').value;
    const member = document.getElementById('f_member').value;
    window.location.href = `laporan.php?export=excel&dari=${dari}&sampai=${sampai}&metode=${metode}&member=${member}`;
}

<?php if(!empty($cLabels)): ?>
window.addEventListener('load', function() {
    const canvas = document.getElementById('chartLaporan');
    if (!canvas || typeof Chart === 'undefined') return;
    const ctx  = canvas.getContext('2d');
    const grad = ctx.createLinearGradient(0,0,0,240);
    grad.addColorStop(0,'rgba(99,102,241,0.4)');
    grad.addColorStop(1,'rgba(99,102,241,0.01)');

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?= json_encode($cLabels) ?>,
            datasets: [{
                label: 'Omset',
                data: <?= json_encode($cData) ?>,
                backgroundColor: grad,
                borderColor: '#6366f1',
                borderWidth: 2,
                borderRadius: 6,
                borderSkipped: false
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor:'#0f1825',titleColor:'#f1f5f9',bodyColor:'#94a3b8',
                    borderColor:'rgba(255,255,255,0.08)',borderWidth:1,padding:12,
                    callbacks: { label: c => ' Rp '+c.raw.toLocaleString('id-ID') }
                }
            },
            scales: {
                x: { grid:{color:'rgba(255,255,255,0.04)'}, ticks:{color:'#475569',font:{size:11}} },
                y: { grid:{color:'rgba(255,255,255,0.04)'}, ticks:{color:'#475569',font:{size:11},callback: v => 'Rp '+(v>=1000000?(v/1000000).toFixed(1)+'jt':v>=1000?(v/1000).toFixed(0)+'rb':v)}, beginAtZero:true }
            }
        }
    });
});
<?php endif; ?>

// Enter key applies filter on date fields
['f_dari','f_sampai'].forEach(id => {
    document.getElementById(id).addEventListener('keydown', e => { if(e.key==='Enter') applyFilter(); });
});
</script>
<?php include 'includes/footer.php'; ?>
