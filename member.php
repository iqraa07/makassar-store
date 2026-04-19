<?php
// ============================================================
// MEMBER — Manajemen Pelanggan
// ============================================================
require_once 'config/database.php';
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $id     = (int)($_POST['id'] ?? 0);
        $nama   = sanitize($_POST['nama'] ?? '');
        $email  = sanitize($_POST['email'] ?? '');
        $telp   = sanitize($_POST['telepon'] ?? '');
        $alamat = sanitize($_POST['alamat'] ?? '');
        $tgl    = sanitize($_POST['tanggal_lahir'] ?? '');
        $jk     = in_array($_POST['jenis_kelamin'] ?? 'L', ['L','P']) ? $_POST['jenis_kelamin'] : 'L';
        $status = in_array($_POST['status'] ?? 'aktif', ['aktif','nonaktif']) ? $_POST['status'] : 'aktif';

        if (!$nama) jsonResponse(['success'=>false,'message'=>'Nama wajib diisi.']);

        if ($id > 0) {
            $st = $db->prepare("UPDATE tbl_member SET nama=?,email=?,telepon=?,alamat=?,tanggal_lahir=?,jenis_kelamin=?,status=? WHERE id=?");
            $tglVal = $tgl ?: null;
            $st->bind_param('sssssss i', $nama,$email,$telp,$alamat,$tglVal,$jk,$status,$id);
            // fix
            $st->close();
            $tglQ = $tgl ? "'$tgl'" : 'NULL';
            $db->query("UPDATE tbl_member SET nama='".addslashes($nama)."',email='".addslashes($email)."',telepon='".addslashes($telp)."',alamat='".addslashes($alamat)."',tanggal_lahir=$tglQ,jenis_kelamin='$jk',status='$status' WHERE id=$id");
            jsonResponse(['success'=>$db->affected_rows>=0,'message'=>'Data member diperbarui.']);
        } else {
            $kode = generateKode('MBR', 'tbl_member', 'kode_member');
            $tglQ = $tgl ? "'$tgl'" : 'NULL';
            $db->query("INSERT INTO tbl_member(kode_member,nama,email,telepon,alamat,tanggal_lahir,jenis_kelamin,status) VALUES('$kode','".addslashes($nama)."','".addslashes($email)."','".addslashes($telp)."','".addslashes($alamat)."',$tglQ,'$jk','$status')");
            if ($db->insert_id) jsonResponse(['success'=>true,'message'=>"Member $kode berhasil ditambahkan."]);
            else jsonResponse(['success'=>false,'message'=>'Gagal: '.$db->error]);
        }
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $db->query("UPDATE tbl_transaksi SET id_member=NULL WHERE id_member=$id");
        $db->query("DELETE FROM tbl_member WHERE id=$id");
        jsonResponse(['success'=>true,'message'=>'Member dihapus.']);
    }

    if ($action === 'get_history') {
        $id = (int)($_POST['id'] ?? 0);
        $res = $db->query("
            SELECT t.kode_transaksi, t.total_bayar, t.metode_bayar, t.created_at,
                   GROUP_CONCAT(dt.nama_barang SEPARATOR ', ') as items,
                   COUNT(dt.id) as item_count
            FROM tbl_transaksi t
            JOIN tbl_detail_transaksi dt ON t.id = dt.id_transaksi
            WHERE t.id_member = $id
            GROUP BY t.id
            ORDER BY t.created_at DESC
            LIMIT 20
        ");
        $rows = [];
        while ($r = $res->fetch_assoc()) $rows[] = $r;
        jsonResponse(['success'=>true,'data'=>$rows]);
    }
    exit;
}

$search = $_GET['q'] ?? '';
$status = $_GET['status'] ?? 'semua';
$where = ['1=1'];
if ($search) $where[] = "(nama LIKE '%".addslashes($search)."%' OR kode_member LIKE '%".addslashes($search)."%' OR telepon LIKE '%".addslashes($search)."%')";
if ($status !== 'semua') $where[] = "status='$status'";
$whereStr = implode(' AND ', $where);

$memberList = $db->query("SELECT * FROM tbl_member WHERE $whereStr ORDER BY total_belanja DESC");
$totalMember = $db->query("SELECT COUNT(*) as c FROM tbl_member")->fetch_assoc()['c'];
$totalAktif  = $db->query("SELECT COUNT(*) as c FROM tbl_member WHERE status='aktif'")->fetch_assoc()['c'];

$currentPage = 'member';
$pageTitle   = 'Manajemen Member';
$pageSub     = 'Data Pelanggan & Riwayat Belanja';
include 'includes/header.php';
?>

<div class="toolbar">
    <div class="search-input-wrap">
        <i class="fa-solid fa-search"></i>
        <input type="text" class="form-control" id="searchInput" placeholder="Cari nama, kode, atau nomor telepon..." value="<?= htmlspecialchars($search) ?>">
    </div>
    <div style="display:flex;gap:6px">
        <a href="?status=semua" class="btn btn-ghost btn-sm <?= $status==='semua'?'btn-outline':'' ?>">Semua (<?= $totalMember ?>)</a>
        <a href="?status=aktif" class="btn btn-ghost btn-sm <?= $status==='aktif'?'btn-outline':'' ?>">Aktif (<?= $totalAktif ?>)</a>
    </div>
    <button class="btn btn-primary" onclick="openModal('modalMember');resetMemberForm()">
        <i class="fa-solid fa-user-plus"></i> Tambah Member
    </button>
</div>

<!-- Stats Member -->
<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-bottom:20px">
    <?php
    $topMember = $db->query("SELECT nama, total_belanja FROM tbl_member ORDER BY total_belanja DESC LIMIT 1")->fetch_assoc();
    $totalPoin = $db->query("SELECT COALESCE(SUM(poin),0) as c FROM tbl_member")->fetch_assoc()['c'];
    $avgBelanja = $totalMember > 0 ? ($db->query("SELECT AVG(total_belanja) as c FROM tbl_member")->fetch_assoc()['c']) : 0;
    ?>
    <div class="card" style="border-color:var(--primary-glow)">
        <div class="card-body" style="display:flex;align-items:center;gap:14px">
            <div style="width:44px;height:44px;background:var(--primary-soft);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:20px;flex-shrink:0">👑</div>
            <div>
                <div style="font-size:11px;color:var(--text-muted);font-weight:600;text-transform:uppercase;letter-spacing:0.5px">Top Member</div>
                <div style="font-size:14px;font-weight:700;color:var(--text)"><?= $topMember ? htmlspecialchars($topMember['nama']) : '-' ?></div>
                <div style="font-size:12px;color:var(--text-muted)"><?= $topMember ? formatRupiah($topMember['total_belanja']) : '-' ?></div>
            </div>
        </div>
    </div>
    <div class="card" style="border-color:rgba(245,158,11,0.2)">
        <div class="card-body" style="display:flex;align-items:center;gap:14px">
            <div style="width:44px;height:44px;background:var(--amber-soft);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:20px;flex-shrink:0">⭐</div>
            <div>
                <div style="font-size:11px;color:var(--text-muted);font-weight:600;text-transform:uppercase;letter-spacing:0.5px">Total Poin Aktif</div>
                <div style="font-size:20px;font-weight:800;color:var(--amber)"><?= number_format($totalPoin) ?></div>
                <div style="font-size:12px;color:var(--text-muted)">poin terkumpul</div>
            </div>
        </div>
    </div>
    <div class="card" style="border-color:rgba(16,185,129,0.2)">
        <div class="card-body" style="display:flex;align-items:center;gap:14px">
            <div style="width:44px;height:44px;background:var(--success-soft);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:20px;flex-shrink:0">📈</div>
            <div>
                <div style="font-size:11px;color:var(--text-muted);font-weight:600;text-transform:uppercase;letter-spacing:0.5px">Avg Belanja / Member</div>
                <div style="font-size:16px;font-weight:800;color:var(--success)"><?= formatRupiah($avgBelanja) ?></div>
                <div style="font-size:12px;color:var(--text-muted)">rata-rata total belanja</div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Kode</th>
                    <th>Nama Member</th>
                    <th>Kontak</th>
                    <th>Poin</th>
                    <th>Total Belanja</th>
                    <th>Transaksi</th>
                    <th>Status</th>
                    <th width="120">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php $no=1; while($m = $memberList->fetch_assoc()):
                    $initial = strtoupper(substr($m['nama'],0,1));
                    $colors = ['#6366f1','#f59e0b','#10b981','#3b82f6','#ef4444','#8b5cf6'];
                    $color  = $colors[crc32($m['nama']) % count($colors)];
                ?>
                <tr>
                    <td class="text-muted"><?= $no++ ?></td>
                    <td><span class="td-code"><?= $m['kode_member'] ?></span></td>
                    <td>
                        <div style="display:flex;align-items:center;gap:10px">
                            <div style="width:36px;height:36px;border-radius:50%;background:<?= $color ?>22;color:<?= $color ?>;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:15px;border:2px solid <?= $color ?>44;flex-shrink:0"><?= $initial ?></div>
                            <div>
                                <div class="td-primary"><?= htmlspecialchars($m['nama']) ?></div>
                                <div class="text-xs text-muted"><?= $m['jenis_kelamin'] === 'L' ? '👨 Laki-laki' : '👩 Perempuan' ?><?= $m['tanggal_lahir'] ? ' · ' . date('d M Y', strtotime($m['tanggal_lahir'])) : '' ?></div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div style="font-size:13px"><?= $m['email'] ?: '-' ?></div>
                        <div class="text-xs text-muted"><?= $m['telepon'] ?: '-' ?></div>
                    </td>
                    <td>
                        <div style="display:flex;align-items:center;gap:6px">
                            <span style="color:var(--amber);font-size:12px">⭐</span>
                            <span style="font-weight:700;color:var(--text)"><?= number_format($m['poin']) ?></span>
                        </div>
                    </td>
                    <td class="td-primary"><?= formatRupiah($m['total_belanja']) ?></td>
                    <td><span class="badge badge-info"><?= $m['jumlah_transaksi'] ?>x</span></td>
                    <td>
                        <span class="badge <?= $m['status']==='aktif' ? 'badge-success' : 'badge-danger' ?>"><?= ucfirst($m['status']) ?></span>
                    </td>
                    <td>
                        <div style="display:flex;gap:5px">
                            <button class="btn btn-icon btn-ghost" title="Riwayat Belanja" onclick="lihatHistory(<?= $m['id'] ?>,'<?= htmlspecialchars($m['nama'],ENT_QUOTES) ?>')">
                                <i class="fa-solid fa-clock-rotate-left"></i>
                            </button>
                            <button class="btn btn-icon btn-ghost" title="Edit" onclick='editMember(<?= json_encode($m) ?>)'>
                                <i class="fa-solid fa-pencil"></i>
                            </button>
                            <button class="btn btn-icon" style="background:var(--danger-soft);color:var(--danger)" title="Hapus" onclick="hapusMember(<?= $m['id'] ?>,'<?= htmlspecialchars($m['nama'],ENT_QUOTES) ?>')">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ═══ MODAL MEMBER ═══ -->
<div class="modal-overlay" id="modalMember">
    <div class="modal modal-lg">
        <div class="modal-header">
            <h3 id="memberModalTitle">Tambah Member Baru</h3>
            <button class="modal-close" onclick="closeModal('modalMember')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="member_id">
            <div class="form-row form-row-2">
                <div class="form-group">
                    <label class="form-label">Nama Lengkap <span style="color:var(--danger)">*</span></label>
                    <input type="text" id="m_nama" class="form-control" placeholder="Nama lengkap member">
                </div>
                <div class="form-group">
                    <label class="form-label">Jenis Kelamin</label>
                    <select id="m_jk" class="form-control">
                        <option value="L">👨 Laki-laki</option>
                        <option value="P">👩 Perempuan</option>
                    </select>
                </div>
            </div>
            <div class="form-row form-row-2">
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" id="m_email" class="form-control" placeholder="contoh@email.com">
                </div>
                <div class="form-group">
                    <label class="form-label">Nomor Telepon</label>
                    <input type="tel" id="m_telp" class="form-control" placeholder="08xxxxxxxxxx">
                </div>
            </div>
            <div class="form-row form-row-2">
                <div class="form-group">
                    <label class="form-label">Tanggal Lahir</label>
                    <input type="date" id="m_tgl" class="form-control">
                </div>
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select id="m_status" class="form-control">
                        <option value="aktif">Aktif</option>
                        <option value="nonaktif">Non-Aktif</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Alamat</label>
                <textarea id="m_alamat" class="form-control" rows="2" placeholder="Alamat lengkap member"></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-ghost" onclick="closeModal('modalMember')">Batal</button>
            <button class="btn btn-primary" id="btnSaveMember" onclick="saveMember()">
                <i class="fa-solid fa-save"></i> Simpan
            </button>
        </div>
    </div>
</div>

<!-- ═══ MODAL HISTORY ═══ -->
<div class="modal-overlay" id="modalHistory">
    <div class="modal modal-lg">
        <div class="modal-header">
            <h3 id="historyTitle">Riwayat Belanja</h3>
            <button class="modal-close" onclick="closeModal('modalHistory')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="modal-body" id="historyBody" style="padding:0">
            <div style="text-align:center;padding:40px;color:var(--text-muted)"><span class="loading"></span></div>
        </div>
    </div>
</div>

<script>
document.getElementById('searchInput').addEventListener('input', debounce(function() {
    window.location.href = `member.php?q=${encodeURIComponent(this.value)}&status=<?= $status ?>`;
}, 500));

function resetMemberForm() {
    document.getElementById('member_id').value = '';
    ['m_nama','m_email','m_telp','m_alamat','m_tgl'].forEach(id => document.getElementById(id).value = '');
    document.getElementById('m_jk').value = 'L';
    document.getElementById('m_status').value = 'aktif';
    document.getElementById('memberModalTitle').textContent = 'Tambah Member Baru';
}

function editMember(m) {
    resetMemberForm();
    document.getElementById('memberModalTitle').textContent = 'Edit Member';
    document.getElementById('member_id').value = m.id;
    document.getElementById('m_nama').value = m.nama;
    document.getElementById('m_email').value = m.email || '';
    document.getElementById('m_telp').value = m.telepon || '';
    document.getElementById('m_alamat').value = m.alamat || '';
    document.getElementById('m_tgl').value = m.tanggal_lahir || '';
    document.getElementById('m_jk').value = m.jenis_kelamin;
    document.getElementById('m_status').value = m.status;
    openModal('modalMember');
}

async function saveMember() {
    const btn = document.getElementById('btnSaveMember');
    btn.disabled = true;
    btn.innerHTML = '<span class="loading"></span> Menyimpan...';
    const body = new URLSearchParams({
        action:'save',
        id: document.getElementById('member_id').value,
        nama: document.getElementById('m_nama').value,
        email: document.getElementById('m_email').value,
        telepon: document.getElementById('m_telp').value,
        alamat: document.getElementById('m_alamat').value,
        tanggal_lahir: document.getElementById('m_tgl').value,
        jenis_kelamin: document.getElementById('m_jk').value,
        status: document.getElementById('m_status').value
    });
    const res = await fetchJSON('member.php',{method:'POST',body});
    btn.disabled = false;
    btn.innerHTML = '<i class="fa-solid fa-save"></i> Simpan';
    if (res.success) { showToast('success','Berhasil!',res.message); closeModal('modalMember'); setTimeout(()=>location.reload(),900); }
    else showToast('error','Gagal',res.message);
}

async function hapusMember(id, nama) {
    if (!confirm(`Hapus member "${nama}"?`)) return;
    const res = await fetchJSON('member.php',{method:'POST',body:new URLSearchParams({action:'delete',id})});
    if (res.success) { showToast('success','Dihapus',res.message); setTimeout(()=>location.reload(),900); }
    else showToast('error','Gagal',res.message);
}

async function lihatHistory(id, nama) {
    document.getElementById('historyTitle').textContent = `Riwayat Belanja — ${nama}`;
    document.getElementById('historyBody').innerHTML = '<div style="text-align:center;padding:40px"><span class="loading" style="width:28px;height:28px;border-width:3px;border-top-color:var(--primary)"></span></div>';
    openModal('modalHistory');
    const res = await fetchJSON('member.php',{method:'POST',body:new URLSearchParams({action:'get_history',id})});
    if (!res.success || res.data.length === 0) {
        document.getElementById('historyBody').innerHTML = '<div class="empty-state"><i class="fa-solid fa-clock-rotate-left"></i><p>Belum ada riwayat belanja</p></div>';
        return;
    }
    document.getElementById('historyBody').innerHTML = `
    <table class="data-table">
        <thead><tr><th>Kode Transaksi</th><th>Item</th><th>Total</th><th>Metode</th><th>Tanggal</th></tr></thead>
        <tbody>
        ${res.data.map(t => `
        <tr>
            <td><span class="td-code">${t.kode_transaksi}</span></td>
            <td style="max-width:200px;font-size:12px;color:var(--text-muted)">${t.items.length > 60 ? t.items.substring(0,60)+'...' : t.items}</td>
            <td class="td-primary">${'Rp '+Number(t.total_bayar).toLocaleString('id-ID')}</td>
            <td><span class="badge badge-info">${t.metode_bayar}</span></td>
            <td class="text-muted">${new Date(t.created_at).toLocaleDateString('id-ID',{day:'2-digit',month:'short',year:'numeric'})}</td>
        </tr>`).join('')}
        </tbody>
    </table>`;
}
</script>
<?php include 'includes/footer.php'; ?>
