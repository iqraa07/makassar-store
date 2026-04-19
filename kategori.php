<?php
// ============================================================
// KATEGORI — Manajemen Kategori Barang
// ============================================================
require_once 'config/database.php';
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'save') {
        $id   = (int)($_POST['id'] ?? 0);
        $kode = strtoupper(sanitize($_POST['kode_kategori'] ?? ''));
        $nama = sanitize($_POST['nama_kategori'] ?? '');
        $desk = sanitize($_POST['deskripsi'] ?? '');
        if (!$kode || !$nama) jsonResponse(['success'=>false,'message'=>'Kode dan nama wajib diisi.']);
        if ($id > 0) {
            $stmt = $db->prepare("UPDATE tbl_kategori SET kode_kategori=?,nama_kategori=?,deskripsi=? WHERE id=?");
            $stmt->bind_param('sssi',$kode,$nama,$desk,$id);
        } else {
            $stmt = $db->prepare("INSERT INTO tbl_kategori(kode_kategori,nama_kategori,deskripsi) VALUES(?,?,?)");
            $stmt->bind_param('sss',$kode,$nama,$desk);
        }
        if ($stmt->execute()) jsonResponse(['success'=>true,'message'=>$id>0?'Kategori diperbarui.':'Kategori ditambahkan.']);
        else jsonResponse(['success'=>false,'message'=>'Error: '.$db->error]);
    }
    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $used = $db->query("SELECT COUNT(*) as c FROM tbl_barang WHERE id_kategori=$id")->fetch_assoc()['c'];
        if ($used > 0) jsonResponse(['success'=>false,'message'=>"Kategori tidak bisa dihapus, masih digunakan oleh $used barang."]);
        $db->query("DELETE FROM tbl_kategori WHERE id=$id");
        jsonResponse(['success'=>$db->affected_rows>0,'message'=>'Kategori dihapus.']);
    }
    exit;
}

$kategoriList = $db->query("
    SELECT k.*, COUNT(b.id) as total_barang, COALESCE(SUM(b.stok),0) as total_stok
    FROM tbl_kategori k
    LEFT JOIN tbl_barang b ON k.id = b.id_kategori
    GROUP BY k.id
    ORDER BY k.nama_kategori ASC
");

$currentPage = 'kategori';
$pageTitle   = 'Kategori Barang';
$pageSub     = 'Pengelompokan & Klasifikasi Produk';
include 'includes/header.php';
?>

<div class="toolbar">
    <div class="search-input-wrap">
        <i class="fa-solid fa-search"></i>
        <input type="text" class="form-control" id="searchInput" placeholder="Cari kategori...">
    </div>
    <button class="btn btn-primary" onclick="openModal('modalKategori');resetKatForm()">
        <i class="fa-solid fa-plus"></i> Tambah Kategori
    </button>
</div>

<div class="card">
    <div class="table-wrap">
        <table class="data-table" id="tableKategori">
            <thead>
                <tr>
                    <th width="40">#</th>
                    <th>Kode</th>
                    <th>Nama Kategori</th>
                    <th>Deskripsi</th>
                    <th>Jumlah Barang</th>
                    <th>Total Stok</th>
                    <th>Dibuat</th>
                    <th width="100">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $emojis = ['🥤','🍟','🧹','🛒','🧴','🏠','📱','👗','✏️','🔧'];
                $no = 1; $ei = 0;
                while ($k = $kategoriList->fetch_assoc()):
                    $emoji = $emojis[$ei++ % count($emojis)];
                ?>
                <tr id="kat-row-<?= $k['id'] ?>">
                    <td class="text-muted"><?= $no++ ?></td>
                    <td><span class="td-code"><?= htmlspecialchars($k['kode_kategori']) ?></span></td>
                    <td>
                        <div style="display:flex;align-items:center;gap:10px">
                            <span style="font-size:22px"><?= $emoji ?></span>
                            <span class="td-primary"><?= htmlspecialchars($k['nama_kategori']) ?></span>
                        </div>
                    </td>
                    <td style="color:var(--text-muted);max-width:220px"><?= $k['deskripsi'] ? htmlspecialchars(substr($k['deskripsi'],0,60)).'...' : '-' ?></td>
                    <td>
                        <span class="badge badge-primary"><?= $k['total_barang'] ?> produk</span>
                    </td>
                    <td class="td-primary"><?= number_format($k['total_stok']) ?></td>
                    <td class="text-muted"><?= date('d M Y', strtotime($k['created_at'])) ?></td>
                    <td>
                        <div style="display:flex;gap:5px">
                            <button class="btn btn-icon btn-ghost" onclick='editKategori(<?= json_encode($k) ?>)' title="Edit">
                                <i class="fa-solid fa-pencil"></i>
                            </button>
                            <button class="btn btn-icon" style="background:var(--danger-soft);color:var(--danger)" onclick="hapusKategori(<?= $k['id'] ?>,'<?= htmlspecialchars($k['nama_kategori'],ENT_QUOTES) ?>')" title="Hapus">
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

<!-- ═══ MODAL KATEGORI ═══ -->
<div class="modal-overlay" id="modalKategori">
    <div class="modal" style="max-width:460px">
        <div class="modal-header">
            <h3 id="katModalTitle">Tambah Kategori</h3>
            <button class="modal-close" onclick="closeModal('modalKategori')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="kat_id">
            <div class="form-group">
                <label class="form-label">Kode Kategori <span style="color:var(--danger)">*</span></label>
                <input type="text" id="kode_kategori" class="form-control" placeholder="KAT001" style="text-transform:uppercase" maxlength="10">
            </div>
            <div class="form-group">
                <label class="form-label">Nama Kategori <span style="color:var(--danger)">*</span></label>
                <input type="text" id="nama_kategori" class="form-control" placeholder="Contoh: Minuman, Makanan Ringan...">
            </div>
            <div class="form-group">
                <label class="form-label">Deskripsi</label>
                <textarea id="desk_kategori" class="form-control" rows="2" placeholder="Deskripsi kategori (opsional)"></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-ghost" onclick="closeModal('modalKategori')">Batal</button>
            <button class="btn btn-primary" id="btnSaveKat" onclick="saveKategori()">
                <i class="fa-solid fa-save"></i> Simpan
            </button>
        </div>
    </div>
</div>

<script>
// Search filter
document.getElementById('searchInput').addEventListener('input', debounce(function() {
    const q = this.value.toLowerCase();
    document.querySelectorAll('#tableKategori tbody tr').forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
}, 250));

function resetKatForm() {
    document.getElementById('kat_id').value = '';
    document.getElementById('kode_kategori').value = '';
    document.getElementById('nama_kategori').value = '';
    document.getElementById('desk_kategori').value = '';
    document.getElementById('katModalTitle').textContent = 'Tambah Kategori';
}

function editKategori(k) {
    resetKatForm();
    document.getElementById('katModalTitle').textContent = 'Edit Kategori';
    document.getElementById('kat_id').value = k.id;
    document.getElementById('kode_kategori').value = k.kode_kategori;
    document.getElementById('nama_kategori').value = k.nama_kategori;
    document.getElementById('desk_kategori').value = k.deskripsi || '';
    openModal('modalKategori');
}

async function saveKategori() {
    const btn = document.getElementById('btnSaveKat');
    btn.disabled = true;
    btn.innerHTML = '<span class="loading"></span> Menyimpan...';
    const body = new URLSearchParams({
        action: 'save',
        id: document.getElementById('kat_id').value,
        kode_kategori: document.getElementById('kode_kategori').value,
        nama_kategori: document.getElementById('nama_kategori').value,
        deskripsi: document.getElementById('desk_kategori').value
    });
    const res = await fetchJSON('kategori.php',{method:'POST',body});
    btn.disabled = false;
    btn.innerHTML = '<i class="fa-solid fa-save"></i> Simpan';
    if (res.success) {
        showToast('success','Berhasil!', res.message);
        closeModal('modalKategori');
        setTimeout(()=>location.reload(),900);
    } else showToast('error','Gagal', res.message);
}

async function hapusKategori(id, nama) {
    if (!confirm(`Hapus kategori "${nama}"?`)) return;
    const res = await fetchJSON('kategori.php',{method:'POST',body:new URLSearchParams({action:'delete',id})});
    if (res.success) { showToast('success','Dihapus', res.message); setTimeout(()=>location.reload(),900); }
    else showToast('error','Tidak Bisa Dihapus', res.message);
}
</script>
<?php include 'includes/footer.php'; ?>
