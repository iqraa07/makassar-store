<?php
// ============================================================
// BARANG — Manajemen Stok Produk
// ============================================================
require_once 'config/database.php';
$db = getDB();

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $id       = (int)($_POST['id'] ?? 0);
        $kode     = sanitize($_POST['kode_barang'] ?? '');
        $nama     = sanitize($_POST['nama_barang'] ?? '');
        $id_kat   = (int)($_POST['id_kategori'] ?? 0);
        $h_beli   = (float)($_POST['harga_beli'] ?? 0);
        $h_jual   = (float)($_POST['harga_jual'] ?? 0);
        $stok     = (int)($_POST['stok'] ?? 0);
        $stok_min = (int)($_POST['stok_minimum'] ?? 5);
        $satuan   = sanitize($_POST['satuan'] ?? 'pcs');
        $deskripsi= sanitize($_POST['deskripsi'] ?? '');

        if (!$kode || !$nama || $h_jual <= 0) {
            jsonResponse(['success'=>false,'message'=>'Kode, nama, dan harga jual wajib diisi.']);
        }

        if ($id > 0) {
            $stmt = $db->prepare("UPDATE tbl_barang SET kode_barang=?,nama_barang=?,id_kategori=?,harga_beli=?,harga_jual=?,stok=?,stok_minimum=?,satuan=?,deskripsi=? WHERE id=?");
            $stmt->bind_param('ssiiddiisi', $kode,$nama,$id_kat,$h_beli,$h_jual,$stok,$stok_min,$satuan,$deskripsi,$id);
        } else {
            $stmt = $db->prepare("INSERT INTO tbl_barang (kode_barang,nama_barang,id_kategori,harga_beli,harga_jual,stok,stok_minimum,satuan,deskripsi) VALUES(?,?,?,?,?,?,?,?,?)");
            $stmt->bind_param('ssiiddiss', $kode,$nama,$id_kat,$h_beli,$h_jual,$stok,$stok_min,$satuan,$deskripsi);
        }

        if ($stmt->execute()) {
            jsonResponse(['success'=>true,'message'=>$id>0 ? 'Barang berhasil diperbarui.' : 'Barang berhasil ditambahkan.']);
        } else {
            jsonResponse(['success'=>false,'message'=>'Gagal menyimpan: ' . $db->error]);
        }
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $check = $db->query("SELECT COUNT(*) as c FROM tbl_detail_transaksi WHERE id_barang=$id")->fetch_assoc()['c'];
        if ($check > 0) {
            jsonResponse(['success'=>false,'message'=>'Barang tidak dapat dihapus karena sudah ada di transaksi.']);
        }
        $db->query("DELETE FROM tbl_barang WHERE id=$id");
        jsonResponse(['success'=>$db->affected_rows > 0,'message'=>'Barang dihapus.']);
    }

    if ($action === 'update_stok') {
        $id    = (int)($_POST['id'] ?? 0);
        $tambah = (int)($_POST['tambah'] ?? 0);
        $db->query("UPDATE tbl_barang SET stok = stok + $tambah WHERE id = $id");
        jsonResponse(['success'=>true,'message'=>"Stok berhasil ditambah $tambah unit."]);
    }
    exit;
}

// Filter
$filter  = $_GET['filter'] ?? 'all';
$search  = $_GET['q'] ?? '';
$katId   = (int)($_GET['kat'] ?? 0);

$where = ['1=1'];
if ($filter === 'kritis') $where[] = 'b.stok <= b.stok_minimum';
if ($filter === 'habis')  $where[] = 'b.stok = 0';
if ($search) $where[] = "(b.nama_barang LIKE '%".addslashes($search)."%' OR b.kode_barang LIKE '%".addslashes($search)."%')";
if ($katId) $where[] = "b.id_kategori = $katId";

$whereStr = implode(' AND ', $where);
$barangList = $db->query("
    SELECT b.*, k.nama_kategori
    FROM tbl_barang b
    LEFT JOIN tbl_kategori k ON b.id_kategori = k.id
    WHERE $whereStr
    ORDER BY b.nama_barang ASC
");
$kategoriList = $db->query("SELECT * FROM tbl_kategori ORDER BY nama_kategori");
$stokKritis = $db->query("SELECT COUNT(*) as c FROM tbl_barang WHERE stok <= stok_minimum")->fetch_assoc()['c'];

$semua = $db->query("SELECT COUNT(*) as c FROM tbl_barang")->fetch_assoc()['c'];
$habis = $db->query("SELECT COUNT(*) as c FROM tbl_barang WHERE stok=0")->fetch_assoc()['c'];

$currentPage = 'barang';
$pageTitle   = 'Stok Barang';
$pageSub     = 'Manajemen Produk & Inventori';
include 'includes/header.php';
?>

<!-- Toolbar -->
<div class="toolbar">
    <div class="search-input-wrap">
        <i class="fa-solid fa-search"></i>
        <input type="text" class="form-control" id="searchInput" placeholder="Cari nama atau kode barang..." value="<?= htmlspecialchars($search) ?>">
    </div>
    <select class="form-control" id="katFilter" style="width:180px" onchange="applyFilter()">
        <option value="">Semua Kategori</option>
        <?php
        $kategoriList->data_seek(0);
        while($k = $kategoriList->fetch_assoc()):
        ?>
        <option value="<?= $k['id'] ?>" <?= $katId==$k['id']?'selected':'' ?>><?= htmlspecialchars($k['nama_kategori']) ?></option>
        <?php endwhile; ?>
    </select>

    <div style="display:flex;gap:6px">
        <a href="?filter=all"    class="btn btn-ghost btn-sm <?= $filter==='all'?'btn-outline':'' ?>">Semua (<?= $semua ?>)</a>
        <a href="?filter=kritis" class="btn btn-ghost btn-sm <?= $filter==='kritis'?'btn-outline':'' ?>" style="<?= $stokKritis>0?'color:var(--warning)':'' ?>">
            <i class="fa-solid fa-triangle-exclamation"></i> Kritis (<?= $stokKritis ?>)
        </a>
        <a href="?filter=habis"  class="btn btn-ghost btn-sm <?= $filter==='habis'?'btn-outline':'' ?>" style="<?= $habis>0?'color:var(--danger)':'' ?>">Habis (<?= $habis ?>)</a>
    </div>

    <button class="btn btn-primary" onclick="openModal('modalBarang');resetForm()">
        <i class="fa-solid fa-plus"></i> Tambah Barang
    </button>
</div>

<!-- Table -->
<div class="card">
    <div class="table-wrap">
        <table class="data-table" id="tableBarang">
            <thead>
                <tr>
                    <th width="40">#</th>
                    <th>Kode</th>
                    <th>Nama Barang</th>
                    <th>Kategori</th>
                    <th>Harga Beli</th>
                    <th>Harga Jual</th>
                    <th>Stok</th>
                    <th>Satuan</th>
                    <th>Status</th>
                    <th width="120">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php $no=1; while($b = $barangList->fetch_assoc()):
                    $stokPct = $b['stok_minimum']>0 ? ($b['stok'] / ($b['stok_minimum']*3))*100 : 100;
                    $stokClass = $b['stok']==0 ? 'danger' : ($b['stok']<=$b['stok_minimum'] ? 'low' : '');
                    $statusBadge = $b['stok']==0 ? 'badge-danger' : ($b['stok']<=$b['stok_minimum'] ? 'badge-warning' : 'badge-success');
                    $statusText  = $b['stok']==0 ? 'Habis' : ($b['stok']<=$b['stok_minimum'] ? 'Kritis' : 'Tersedia');
                    $icons = ['Minuman'=>'🥤','Makanan Ringan'=>'🍟','Kebutuhan Rumah'=>'🧹','Sembako'=>'🛒','Perawatan Diri'=>'🧴'];
                    $icon = $icons[$b['nama_kategori']] ?? '📦';
                ?>
                <tr id="row-<?= $b['id'] ?>">
                    <td class="text-muted"><?= $no++ ?></td>
                    <td><span class="td-code"><?= htmlspecialchars($b['kode_barang']) ?></span></td>
                    <td>
                        <div style="display:flex;align-items:center;gap:10px">
                            <span style="font-size:20px"><?= $icon ?></span>
                            <div>
                                <div class="td-primary"><?= htmlspecialchars($b['nama_barang']) ?></div>
                                <?php if($b['deskripsi']): ?><div class="text-xs text-muted" style="margin-top:1px"><?= htmlspecialchars(substr($b['deskripsi'],0,40)) ?>...</div><?php endif; ?>
                            </div>
                        </div>
                    </td>
                    <td><span class="badge badge-neutral"><?= $b['nama_kategori'] ?? '-' ?></span></td>
                    <td><?= formatRupiah($b['harga_beli']) ?></td>
                    <td class="td-primary"><?= formatRupiah($b['harga_jual']) ?></td>
                    <td>
                        <div style="display:flex;align-items:center;gap:8px">
                            <span style="font-weight:700;color:var(--text);font-size:14px;min-width:24px"><?= $b['stok'] ?></span>
                            <div class="stok-bar-fill" style="width:60px">
                                <div class="stok-bar-val <?= $stokClass ?>" style="width:<?= max(4,min(100,$stokPct)) ?>%"></div>
                            </div>
                        </div>
                    </td>
                    <td class="text-muted"><?= $b['satuan'] ?></td>
                    <td><span class="badge <?= $statusBadge ?>"><?= $statusText ?></span></td>
                    <td>
                        <div style="display:flex;gap:5px">
                            <button class="btn btn-icon btn-ghost" title="Tambah Stok" onclick="tambahStok(<?= $b['id'] ?>,'<?= htmlspecialchars($b['nama_barang'], ENT_QUOTES) ?>')">
                                <i class="fa-solid fa-plus"></i>
                            </button>
                            <button class="btn btn-icon btn-ghost" title="Edit" onclick='editBarang(<?= json_encode($b) ?>)'>
                                <i class="fa-solid fa-pencil"></i>
                            </button>
                            <button class="btn btn-icon" style="background:var(--danger-soft);color:var(--danger)" title="Hapus" onclick="hapusBarang(<?= $b['id'] ?>,'<?= htmlspecialchars($b['nama_barang'], ENT_QUOTES) ?>')">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
                <?php if($no === 1): ?>
                <tr><td colspan="10" class="empty-state"><i class="fa-solid fa-box-open"></i><p>Tidak ada data barang</p></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ═══ MODAL BARANG ═══ -->
<div class="modal-overlay" id="modalBarang">
    <div class="modal">
        <div class="modal-header">
            <h3 id="modalBarangTitle">Tambah Barang</h3>
            <button class="modal-close" onclick="closeModal('modalBarang')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="barang_id">
            <div class="form-row form-row-2">
                <div class="form-group">
                    <label class="form-label">Kode Barang <span style="color:var(--danger)">*</span></label>
                    <input type="text" id="kode_barang" class="form-control" placeholder="BRG001" style="text-transform:uppercase">
                    <div class="form-hint">Kode unik produk</div>
                </div>
                <div class="form-group">
                    <label class="form-label">Satuan <span style="color:var(--danger)">*</span></label>
                    <select id="satuan" class="form-control">
                        <option value="pcs">pcs</option>
                        <option value="botol">botol</option>
                        <option value="kaleng">kaleng</option>
                        <option value="karung">karung</option>
                        <option value="kg">kg</option>
                        <option value="pack">pack</option>
                        <option value="liter">liter</option>
                        <option value="box">box</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Nama Barang <span style="color:var(--danger)">*</span></label>
                <input type="text" id="nama_barang" class="form-control" placeholder="Nama lengkap produk">
            </div>
            <div class="form-group">
                <label class="form-label">Kategori</label>
                <select id="id_kategori" class="form-control">
                    <option value="">-- Pilih Kategori --</option>
                    <?php
                    $kategoriList->data_seek(0);
                    while($k = $kategoriList->fetch_assoc()):
                    ?>
                    <option value="<?= $k['id'] ?>"><?= htmlspecialchars($k['nama_kategori']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-row form-row-2">
                <div class="form-group">
                    <label class="form-label">Harga Beli</label>
                    <div class="input-group prefix">
                        <span class="input-prefix">Rp</span>
                        <input type="number" id="harga_beli" class="form-control" placeholder="0" min="0">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Harga Jual <span style="color:var(--danger)">*</span></label>
                    <div class="input-group prefix">
                        <span class="input-prefix">Rp</span>
                        <input type="number" id="harga_jual" class="form-control" placeholder="0" min="0">
                    </div>
                </div>
            </div>
            <div class="form-row form-row-2">
                <div class="form-group">
                    <label class="form-label">Stok Awal</label>
                    <input type="number" id="stok" class="form-control" placeholder="0" min="0">
                </div>
                <div class="form-group">
                    <label class="form-label">Stok Minimum</label>
                    <input type="number" id="stok_minimum" class="form-control" placeholder="5" min="1" value="5">
                    <div class="form-hint">Alert batas bawah stok</div>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Deskripsi</label>
                <textarea id="deskripsi" class="form-control" rows="2" placeholder="Deskripsi singkat produk (opsional)"></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-ghost" onclick="closeModal('modalBarang')">Batal</button>
            <button class="btn btn-primary" id="btnSaveBarang" onclick="saveBarang()">
                <i class="fa-solid fa-save"></i> Simpan
            </button>
        </div>
    </div>
</div>

<!-- ═══ MODAL TAMBAH STOK ═══ -->
<div class="modal-overlay" id="modalTambahStok">
    <div class="modal" style="max-width:380px">
        <div class="modal-header">
            <h3>Tambah Stok</h3>
            <button class="modal-close" onclick="closeModal('modalTambahStok')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="stok_id">
            <div style="margin-bottom:16px;padding:12px;background:var(--bg-card-2);border-radius:var(--radius-sm);font-size:13.5px;font-weight:600;color:var(--text)" id="stokNamaBarang"></div>
            <div class="form-group">
                <label class="form-label">Jumlah Tambah Stok</label>
                <input type="number" id="stok_tambah" class="form-control" placeholder="0" min="1" style="font-size:18px;font-weight:700;text-align:center">
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-ghost" onclick="closeModal('modalTambahStok')">Batal</button>
            <button class="btn btn-success" onclick="saveStok()"><i class="fa-solid fa-plus"></i> Tambah Stok</button>
        </div>
    </div>
</div>

<script>
const searchInput = document.getElementById('searchInput');
searchInput.addEventListener('input', debounce(() => applyFilter(), 400));

function applyFilter() {
    const q = searchInput.value;
    const kat = document.getElementById('katFilter').value;
    let url = 'barang.php?filter=<?= $filter ?>';
    if (q) url += `&q=${encodeURIComponent(q)}`;
    if (kat) url += `&kat=${kat}`;
    window.location.href = url;
}

function resetForm() {
    document.getElementById('barang_id').value = '';
    document.getElementById('kode_barang').value = '';
    document.getElementById('nama_barang').value = '';
    document.getElementById('id_kategori').value = '';
    document.getElementById('harga_beli').value = '';
    document.getElementById('harga_jual').value = '';
    document.getElementById('stok').value = '';
    document.getElementById('stok_minimum').value = '5';
    document.getElementById('satuan').value = 'pcs';
    document.getElementById('deskripsi').value = '';
    document.getElementById('modalBarangTitle').textContent = 'Tambah Barang';
}

function editBarang(b) {
    resetForm();
    document.getElementById('modalBarangTitle').textContent = 'Edit Barang';
    document.getElementById('barang_id').value = b.id;
    document.getElementById('kode_barang').value = b.kode_barang;
    document.getElementById('nama_barang').value = b.nama_barang;
    document.getElementById('id_kategori').value = b.id_kategori || '';
    document.getElementById('harga_beli').value = b.harga_beli;
    document.getElementById('harga_jual').value = b.harga_jual;
    document.getElementById('stok').value = b.stok;
    document.getElementById('stok_minimum').value = b.stok_minimum;
    document.getElementById('satuan').value = b.satuan;
    document.getElementById('deskripsi').value = b.deskripsi || '';
    openModal('modalBarang');
}

async function saveBarang() {
    const btn = document.getElementById('btnSaveBarang');
    const id = document.getElementById('barang_id').value;
    const body = new URLSearchParams({
        action:'save', id,
        kode_barang: document.getElementById('kode_barang').value,
        nama_barang: document.getElementById('nama_barang').value,
        id_kategori: document.getElementById('id_kategori').value,
        harga_beli:  document.getElementById('harga_beli').value || 0,
        harga_jual:  document.getElementById('harga_jual').value,
        stok:        document.getElementById('stok').value || 0,
        stok_minimum:document.getElementById('stok_minimum').value,
        satuan:      document.getElementById('satuan').value,
        deskripsi:   document.getElementById('deskripsi').value
    });
    btn.disabled = true;
    btn.innerHTML = '<span class="loading"></span> Menyimpan...';
    const res = await fetchJSON('barang.php', {method:'POST', body});
    btn.disabled = false;
    btn.innerHTML = '<i class="fa-solid fa-save"></i> Simpan';
    if (res.success) {
        showToast('success','Berhasil!', res.message);
        closeModal('modalBarang');
        setTimeout(() => location.reload(), 900);
    } else {
        showToast('error','Gagal', res.message);
    }
}

async function hapusBarang(id, nama) {
    if (!confirm(`Hapus barang "${nama}"?\nTindakan ini tidak dapat dibatalkan.`)) return;
    const res = await fetchJSON('barang.php',{method:'POST',body:new URLSearchParams({action:'delete',id})});
    if (res.success) { showToast('success','Dihapus', res.message); setTimeout(()=>location.reload(),900); }
    else showToast('error','Gagal', res.message);
}

function tambahStok(id, nama) {
    document.getElementById('stok_id').value = id;
    document.getElementById('stokNamaBarang').textContent = '📦 ' + nama;
    document.getElementById('stok_tambah').value = '';
    openModal('modalTambahStok');
    setTimeout(()=>document.getElementById('stok_tambah').focus(), 200);
}

async function saveStok() {
    const id = document.getElementById('stok_id').value;
    const tambah = document.getElementById('stok_tambah').value;
    if (!tambah || tambah < 1) { showToast('warning','Peringatan','Masukkan jumlah penambahan stok.'); return; }
    const res = await fetchJSON('barang.php',{method:'POST',body:new URLSearchParams({action:'update_stok',id,tambah})});
    if (res.success) { showToast('success','Stok Diperbarui', res.message); closeModal('modalTambahStok'); setTimeout(()=>location.reload(),900); }
    else showToast('error','Gagal', res.message);
}

// Auto uppercase kode
document.getElementById('kode_barang').addEventListener('input', function() {
    this.value = this.value.toUpperCase();
});
</script>

<?php include 'includes/footer.php'; ?>
