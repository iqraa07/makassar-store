<?php
// ============================================================
// TRANSAKSI — POS Kasir Interface — Makassar Store
// ============================================================
require_once 'config/database.php';
$db = getDB();

// Handle AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Get produk for POS grid
    if ($action === 'get_barang') {
        $katId  = (int)($_POST['kat_id'] ?? 0);
        $search = addslashes($_POST['search'] ?? '');
        $where  = ['1=1']; // tampilkan semua termasuk stok habis
        if ($katId) $where[] = "b.id_kategori = $katId";
        if ($search) $where[] = "(b.nama_barang LIKE '%$search%' OR b.kode_barang LIKE '%$search%')";
        $whereStr = implode(' AND ', $where);
        $result = $db->query("SELECT b.*, k.nama_kategori FROM tbl_barang b LEFT JOIN tbl_kategori k ON b.id_kategori = k.id WHERE $whereStr ORDER BY b.stok DESC, b.nama_barang LIMIT 60");
        $barang = [];
        while ($row = $result->fetch_assoc()) $barang[] = $row;
        jsonResponse(['success'=>true,'data'=>$barang]);
    }

    // Save transaksi
    if ($action === 'save_transaksi') {
        $items     = json_decode($_POST['items'] ?? '[]', true);
        $id_member = (int)($_POST['id_member'] ?? 0);
        $total     = (float)($_POST['total'] ?? 0);
        $diskon    = (float)($_POST['diskon'] ?? 0);
        $bayar     = (float)($_POST['bayar'] ?? 0);
        $metode    = sanitize($_POST['metode'] ?? 'tunai');
        $catatan   = sanitize($_POST['catatan'] ?? '');

        if (empty($items) || $total <= 0) jsonResponse(['success'=>false,'message'=>'Keranjang kosong.']);

        $total_bayar = $total - $diskon;
        $kembalian   = $bayar - $total_bayar;
        if ($kembalian < 0 && $metode === 'tunai') jsonResponse(['success'=>false,'message'=>'Uang bayar kurang.']);

        $kode = generateKode('TRX', 'tbl_transaksi', 'kode_transaksi');

        $db->begin_transaction();
        try {
            $kasir = KASIR_NAME;
            $katQ  = $db->real_escape_string($kode);
            $metQ  = $db->real_escape_string($metode);
            $kasQ  = $db->real_escape_string($kasir);
            $catQ  = $db->real_escape_string($catatan);
            $memQ  = $id_member > 0 ? $id_member : 'NULL';

            $db->query("INSERT INTO tbl_transaksi(kode_transaksi,id_member,total_harga,diskon,total_bayar,uang_bayar,kembalian,metode_bayar,kasir,catatan) VALUES('$katQ',$memQ,$total,$diskon,$total_bayar,$bayar,$kembalian,'$metQ','$kasQ','$catQ')");
            $trxId = $db->insert_id;
            if (!$trxId) throw new Exception('Gagal membuat transaksi: '.$db->error);

            foreach ($items as $item) {
                $idBrg  = (int)$item['id'];
                $qty    = (int)$item['qty'];
                $harga  = (float)$item['harga_jual'];
                $sub    = $qty * $harga;
                $nmBrg  = $db->real_escape_string($item['nama_barang']);
                $db->query("INSERT INTO tbl_detail_transaksi(id_transaksi,id_barang,nama_barang,qty,harga_satuan,subtotal) VALUES($trxId,$idBrg,'$nmBrg',$qty,$harga,$sub)");
                // Kurangi stok, minimum 0 (tidak boleh negatif)
                $db->query("UPDATE tbl_barang SET stok = GREATEST(0, stok - $qty) WHERE id = $idBrg");
            }

            // Update member poin
            if ($id_member > 0) {
                $poin = (int)($total_bayar / 1000);
                $db->query("UPDATE tbl_member SET poin = poin + $poin, total_belanja = total_belanja + $total_bayar, jumlah_transaksi = jumlah_transaksi + 1 WHERE id = $id_member");
            }

            $db->commit();
            jsonResponse(['success'=>true,'message'=>'Transaksi berhasil!','kode'=>$kode,'id'=>$trxId]);
        } catch (Exception $e) {
            $db->rollback();
            jsonResponse(['success'=>false,'message'=>$e->getMessage()]);
        }
    }
    exit;
}

// Load data
$kategoriList = $db->query("SELECT * FROM tbl_kategori ORDER BY nama_kategori");
$memberList   = $db->query("SELECT id, kode_member, nama, poin FROM tbl_member WHERE status='aktif' ORDER BY nama");

$currentPage = 'transaksi';
$pageTitle   = 'Transaksi Kasir';
$pageSub     = 'Point of Sale — Makassar Store';
include 'includes/header.php';
?>

<style>
/* POS specific layout */
.pos-container {
    display: grid;
    grid-template-columns: 1fr 370px;
    gap: 16px;
    height: calc(100vh - var(--topbar-h) - 48px);
}
.pos-panel {
    display: flex;
    flex-direction: column;
    gap: 12px;
    min-height: 0;
}
.pos-products {
    flex: 1;
    overflow-y: auto;
    scrollbar-width: thin;
    scrollbar-color: var(--bg-hover) transparent;
}
</style>

<div class="pos-container">
    <!-- LEFT: Product Browser -->
    <div class="pos-panel">
        <!-- Search -->
        <div style="display:flex;gap:10px">
            <div class="search-input-wrap" style="flex:1">
                <i class="fa-solid fa-barcode"></i>
                <input type="text" id="posSearch" class="form-control" placeholder="Cari nama atau kode barang...">
            </div>
            <button class="btn btn-ghost" onclick="loadBarang()" title="Refresh">
                <i class="fa-solid fa-rotate"></i>
            </button>
        </div>

        <!-- Category Tabs -->
        <div class="category-tabs">
            <button class="cat-tab active" data-kat="0" onclick="setKat(this, 0)">
                🏪 Semua
            </button>
            <?php while($k = $kategoriList->fetch_assoc()): ?>
            <button class="cat-tab" data-kat="<?= $k['id'] ?>" onclick="setKat(this, <?= $k['id'] ?>)">
                <?= htmlspecialchars($k['nama_kategori']) ?>
            </button>
            <?php endwhile; ?>
        </div>

        <!-- Product Grid -->
        <div id="productGrid" class="pos-products">
            <!-- Skeleton loader -->
            <div id="skeletonGrid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(148px,1fr));gap:12px">
                <?php for($i=0;$i<12;$i++): ?>
                <div class="skeleton" style="height:130px;border-radius:12px"></div>
                <?php endfor; ?>
            </div>
        </div>
    </div>

    <!-- RIGHT: Cart -->
    <div class="pos-right">
        <!-- Cart Header -->
        <div class="cart-header">
            <i class="fa-solid fa-shopping-cart" style="color:var(--primary)"></i>
            <h3>Keranjang</h3>
            <span class="cart-count" id="cartCount">0</span>
            <button class="btn btn-ghost btn-sm" onclick="clearCart()" id="btnClearCart" disabled>
                <i class="fa-solid fa-trash"></i>
            </button>
        </div>

        <!-- Member Select -->
        <div style="padding:10px 14px;border-bottom:1px solid var(--border-light)">
            <div style="display:flex;align-items:center;gap:8px">
                <i class="fa-solid fa-user-tag" style="color:var(--text-muted);font-size:13px"></i>
                <select id="selectMember" class="form-control" style="font-size:12.5px;padding:6px 10px">
                    <option value="">👤 Pelanggan Umum</option>
                    <?php while($m = $memberList->fetch_assoc()): ?>
                    <option value="<?= $m['id'] ?>" data-poin="<?= $m['poin'] ?>">
                        <?= htmlspecialchars($m['nama']) ?> (<?= $m['kode_member'] ?>) — <?= $m['poin'] ?> poin
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>
        </div>

        <!-- Cart Items -->
        <div class="cart-items" id="cartItems">
            <div class="cart-empty" id="cartEmpty">
                <i class="fa-solid fa-cart-shopping"></i>
                <p>Keranjang masih kosong.<br>Pilih produk untuk memulai.</p>
            </div>
        </div>

        <!-- Summary -->
        <div class="cart-summary">
            <div class="summary-row">
                <span class="label">Subtotal</span>
                <span class="value" id="subTotal">Rp 0</span>
            </div>
            <div class="summary-row">
                <span class="label">Diskon</span>
                <div style="display:flex;align-items:center;gap:6px">
                    <input type="number" id="diskonInput" value="0" min="0" style="width:90px;background:var(--bg-input);border:1px solid var(--border);border-radius:6px;color:var(--text);padding:3px 8px;font-size:12px;text-align:right" onchange="updateTotal()">
                </div>
            </div>
            <div class="summary-row summary-total">
                <span class="label">Total</span>
                <span class="value" id="grandTotal">Rp 0</span>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="cart-footer">
            <button class="btn btn-primary btn-xl w-full" id="btnBayar" onclick="openPayment()" disabled>
                <i class="fa-solid fa-cash-register"></i>
                Proses Pembayaran
            </button>
        </div>
    </div>
</div>

<!-- ═══ MODAL PEMBAYARAN ═══ -->
<div class="modal-overlay" id="modalBayar">
    <div class="modal" style="max-width:480px">
        <div class="modal-header">
            <h3><i class="fa-solid fa-credit-card" style="margin-right:8px;color:var(--primary)"></i>Proses Pembayaran</h3>
            <button class="modal-close" onclick="closeModal('modalBayar')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="modal-body">
            <!-- Total summary -->
            <div style="background:var(--bg-card-2);border:1px solid var(--border);border-radius:var(--radius);padding:16px;margin-bottom:20px">
                <div style="display:flex;justify-content:space-between;margin-bottom:6px">
                    <span style="color:var(--text-muted);font-size:13px">Total Item</span>
                    <span id="bayarItemCount" style="font-size:13px;font-weight:600;color:var(--text)">0 item</span>
                </div>
                <div style="display:flex;justify-content:space-between;margin-bottom:6px">
                    <span style="color:var(--text-muted);font-size:13px">Subtotal</span>
                    <span id="bayarSubtotal" style="font-size:13px;color:var(--text)">Rp 0</span>
                </div>
                <div style="display:flex;justify-content:space-between;margin-bottom:10px">
                    <span style="color:var(--text-muted);font-size:13px">Diskon</span>
                    <span id="bayarDiskon" style="font-size:13px;color:var(--success)">- Rp 0</span>
                </div>
                <div style="display:flex;justify-content:space-between;border-top:1px solid var(--border);padding-top:10px">
                    <span style="font-size:16px;font-weight:700;color:var(--text)">Total</span>
                    <span id="bayarTotal" style="font-size:22px;font-weight:800;color:var(--amber)">Rp 0</span>
                </div>
            </div>

            <!-- Metode Pembayaran -->
            <div style="margin-bottom:18px">
                <div class="form-label" style="margin-bottom:10px">Metode Pembayaran</div>
                <div class="payment-method">
                    <div class="payment-opt active" onclick="setMetode('tunai',this)" id="pay-tunai">
                        <div class="pay-icon">💵</div>
                        <div class="pay-label">Tunai</div>
                    </div>
                    <div class="payment-opt" onclick="setMetode('qris',this)" id="pay-qris">
                        <div class="pay-icon">📱</div>
                        <div class="pay-label">QRIS</div>
                    </div>
                    <div class="payment-opt" onclick="setMetode('transfer',this)" id="pay-transfer">
                        <div class="pay-icon">🏦</div>
                        <div class="pay-label">Transfer</div>
                    </div>
                </div>
            </div>

            <!-- Uang Bayar -->
            <div class="form-group" id="uangBayarSection">
                <label class="form-label">Uang Bayar</label>
                <div class="input-group prefix">
                    <span class="input-prefix">Rp</span>
                    <input type="number" id="uangBayar" class="form-control" placeholder="0" oninput="hitungKembalian()" style="font-size:16px;font-weight:600">
                </div>
                <div class="quick-amounts" id="quickAmounts">
                    <button class="quick-amt" onclick="setUang('exact')">Pas</button>
                    <button class="quick-amt" onclick="setUang(5000)">+5rb</button>
                    <button class="quick-amt" onclick="setUang(10000)">+10rb</button>
                    <button class="quick-amt" onclick="setUang(20000)">+20rb</button>
                    <button class="quick-amt" onclick="setUang(50000)">50rb</button>
                    <button class="quick-amt" onclick="setUang(100000)">100rb</button>
                    <button class="quick-amt" onclick="setUang(200000)">200rb</button>
                    <button class="quick-amt" onclick="setUang(500000)">500rb</button>
                </div>
            </div>

            <!-- Kembalian -->
            <div class="kembalian-display" id="kembalianBox">
                <div class="kembalian-label">Kembalian</div>
                <div class="kembalian-value" id="kembalianVal">Rp 0</div>
            </div>

            <!-- Catatan -->
            <div class="form-group" style="margin-top:14px;margin-bottom:0">
                <label class="form-label">Catatan (opsional)</label>
                <input type="text" id="catatanTrx" class="form-control" placeholder="Catatan transaksi...">
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-ghost" onclick="closeModal('modalBayar')">Batal</button>
            <button class="btn btn-success btn-lg" id="btnSelesai" onclick="prosesTransaksi()">
                <i class="fa-solid fa-check-circle"></i> Selesaikan Transaksi
            </button>
        </div>
    </div>
</div>

<!-- ═══ MODAL STRUK ═══ -->
<div class="modal-overlay" id="modalStruk">
    <div class="modal" style="max-width:400px">
        <div class="modal-header">
            <h3>Struk Pembayaran</h3>
            <button class="modal-close" onclick="closeModal('modalStruk');newTransaction()"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="modal-body" id="modalStrukContent" style="padding:0">
        </div>
        <div class="modal-footer">
            <button class="btn btn-ghost" onclick="closeModal('modalStruk');newTransaction()">
                <i class="fa-solid fa-plus"></i> Transaksi Baru
            </button>
            <button class="btn btn-primary" onclick="cetakStruk()">
                <i class="fa-solid fa-print"></i> Cetak Struk
            </button>
        </div>
    </div>
</div>

<script>
// ══════════════════════════════════════════
// POS System Logic
// ══════════════════════════════════════════
// Definisi fetchJSON & debounce inline (independen dari footer timing)
async function fetchJSON(url, options) {
    try {
        var res = await fetch(url, options || {});
        return await res.json();
    } catch(err) {
        console.error('Fetch error:', err);
        return { success: false, message: 'Koneksi error' };
    }
}
function debounce(fn, delay) {
    var t;
    return function() {
        var args = arguments;
        clearTimeout(t);
        t = setTimeout(function() { fn.apply(null, args); }, delay);
    };
}

let cart = [];
let activeKat = 0;
let activeMetode = 'tunai';
let lastTrxKode = '';
let lastTrxId = 0;
window._catalogMap = {};

// ─── Load Barang ───
async function loadBarang(reset = false) {
    const search = document.getElementById('posSearch').value;
    const grid   = document.getElementById('productGrid');
    if (reset) activeKat = 0;

    const res = await fetchJSON('transaksi.php', {
        method: 'POST',
        body: new URLSearchParams({ action: 'get_barang', kat_id: activeKat, search })
    });

    if (!res || !res.success) {
        grid.innerHTML = '<div class="empty-state"><i class="fa-solid fa-triangle-exclamation"></i><p>Gagal memuat produk</p></div>';
        return;
    }

    if (!res.data || res.data.length === 0) {
        grid.innerHTML = '<div class="empty-state"><i class="fa-solid fa-box-open"></i><p>Tidak ada produk ditemukan</p></div>';
        return;
    }

    const icons = {
        'Minuman':'🥤','Makanan Ringan':'🍟','Kebutuhan Rumah':'🧹',
        'Sembako':'🛒','Perawatan Diri':'🧴'
    };
    const colors = ['#6366f1','#f59e0b','#10b981','#3b82f6','#ef4444','#8b5cf6','#14b8a6'];

    // Simpan ke katalog untuk event delegation
    window._catalogMap = {};
    res.data.forEach(b => { window._catalogMap[b.id] = b; });

    grid.innerHTML = '<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(148px,1fr));gap:12px">' +
        res.data.map((b, i) => {
            const icon = icons[b.nama_kategori] || '📦';
            const color = colors[i % colors.length];
            const stokHabis = parseInt(b.stok) <= 0;
            const stokClass = parseInt(b.stok) <= parseInt(b.stok_minimum) ? 'low' : '';
            const stokText = stokHabis ? '⚠️ Stok habis' : ('Stok: ' + b.stok + ' ' + b.satuan);
            const opacityStyle = stokHabis ? ';opacity:0.72' : '';
            return '<div class="product-card" data-id="' + b.id + '" style="animation:cardIn 0.3s ease ' + (i*0.04) + 's both' + opacityStyle + '">' +
                '<div class="product-icon" style="background:' + color + '20;color:' + color + '">' + icon + '</div>' +
                '<div class="product-name">' + b.nama_barang + '</div>' +
                '<div class="product-price">Rp ' + Number(b.harga_jual).toLocaleString('id-ID') + '</div>' +
                '<div class="product-stok ' + stokClass + '">' + stokText + '</div>' +
                '</div>';
        }).join('') + '</div>';

    // Smart barcode: 1 hasil + enter → langsung tambah
    if (res.data.length === 1 && search && document.activeElement === document.getElementById('posSearch')) {
        addToCart(res.data[0]);
        document.getElementById('posSearch').value = '';
        showToast('success', 'Ditambah!', res.data[0].nama_barang + ' masuk keranjang 🛒');
    }
}

function setKat(el, katId) {
    document.querySelectorAll('.cat-tab').forEach(t => t.classList.remove('active'));
    el.classList.add('active');
    activeKat = katId;
    loadBarang();
}

// ─── Cart Operations ───
function addToCart(barang) {
    const inCart = cart.find(i => i.id == barang.id);
    if (inCart) {
        inCart.qty++;
    } else {
        cart.push(Object.assign({}, barang, { qty: 1 }));
    }
    renderCart();
    showAddAnimation();
}

function removeFromCart(id) {
    cart = cart.filter(i => i.id != id);
    renderCart();
}

function changeQty(id, delta) {
    const item = cart.find(i => i.id == id);
    if (!item) return;
    item.qty += delta;
    if (item.qty <= 0) { removeFromCart(id); return; }
    renderCart();
}

function clearCart() {
    if (cart.length === 0) return;
    if (!confirm('Kosongkan keranjang?')) return;
    cart = [];
    renderCart();
}

function renderCart() {
    const itemsEl = document.getElementById('cartItems');
    const countEl = document.getElementById('cartCount');
    const btnBayar = document.getElementById('btnBayar');
    const btnClear = document.getElementById('btnClearCart');

    const total = cart.reduce((s, i) => s + i.qty * i.harga_jual, 0);
    const diskon = parseFloat(document.getElementById('diskonInput').value) || 0;
    const grandTotal = Math.max(0, total - diskon);
    const itemCount = cart.reduce((s, i) => s + i.qty, 0);

    countEl.textContent = itemCount;
    btnBayar.disabled = cart.length === 0;
    btnClear.disabled = cart.length === 0;
    document.getElementById('subTotal').textContent = 'Rp ' + total.toLocaleString('id-ID');
    document.getElementById('grandTotal').textContent = 'Rp ' + grandTotal.toLocaleString('id-ID');

    if (cart.length === 0) {
        itemsEl.innerHTML = '<div class="cart-empty"><i class="fa-solid fa-cart-shopping"></i><p>Keranjang masih kosong.<br>Pilih produk untuk memulai.</p></div>';
        return;
    }

    itemsEl.innerHTML = cart.map(item =>
        '<div class="cart-item" id="ci-' + item.id + '">' +
        '<div style="flex:1">' +
        '<div class="cart-item-name">' + item.nama_barang + '</div>' +
        '<div class="cart-item-price">Rp ' + Number(item.harga_jual).toLocaleString('id-ID') + ' / ' + item.satuan + '</div>' +
        '<div style="display:flex;align-items:center;justify-content:space-between;margin-top:6px">' +
        '<div class="cart-qty-ctrl">' +
        '<button class="qty-btn" onclick="changeQty(' + item.id + ',-1)"><i class="fa-solid fa-minus"></i></button>' +
        '<span class="qty-val">' + item.qty + '</span>' +
        '<button class="qty-btn" onclick="changeQty(' + item.id + ',1)"><i class="fa-solid fa-plus"></i></button>' +
        '</div>' +
        '<div class="cart-item-sub">Rp ' + (item.qty * item.harga_jual).toLocaleString('id-ID') + '</div>' +
        '</div></div>' +
        '<button class="cart-item-del" onclick="removeFromCart(' + item.id + ')"><i class="fa-solid fa-xmark"></i></button>' +
        '</div>'
    ).join('');
}

function updateTotal() { renderCart(); }

function showAddAnimation() {
    const count = document.getElementById('cartCount');
    count.style.transform = 'scale(1.4)';
    count.style.transition = 'transform 0.15s ease';
    setTimeout(function() { count.style.transform = 'scale(1)'; }, 150);
}

// ─── Payment ───
function openPayment() {
    if (cart.length === 0) return;
    const total  = cart.reduce((s, i) => s + i.qty * i.harga_jual, 0);
    const diskon = parseFloat(document.getElementById('diskonInput').value) || 0;
    const grand  = Math.max(0, total - diskon);
    const items  = cart.reduce((s, i) => s + i.qty, 0);

    document.getElementById('bayarItemCount').textContent = items + ' item';
    document.getElementById('bayarSubtotal').textContent  = 'Rp ' + total.toLocaleString('id-ID');
    document.getElementById('bayarDiskon').textContent    = '- Rp ' + diskon.toLocaleString('id-ID');
    document.getElementById('bayarTotal').textContent     = 'Rp ' + grand.toLocaleString('id-ID');
    document.getElementById('uangBayar').value = '';
    document.getElementById('kembalianVal').textContent = 'Rp 0';
    document.getElementById('kembalianBox').style.background = 'var(--success-soft)';
    openModal('modalBayar');
    setTimeout(function() { document.getElementById('uangBayar').focus(); }, 300);
}

function setMetode(m, el) {
    activeMetode = m;
    document.querySelectorAll('.payment-opt').forEach(e => e.classList.remove('active'));
    el.classList.add('active');
    var uangSection  = document.getElementById('uangBayarSection');
    var kembalianBox = document.getElementById('kembalianBox');
    if (m === 'tunai') {
        uangSection.style.display = 'block';
        kembalianBox.style.display = 'block';
    } else {
        uangSection.style.display = 'none';
        kembalianBox.style.display = 'none';
    }
}

function setUang(val) {
    var total = parseFloat(document.getElementById('bayarTotal').textContent.replace(/[^0-9]/g,''));
    if (val === 'exact') {
        document.getElementById('uangBayar').value = total;
    } else {
        var current = parseFloat(document.getElementById('uangBayar').value) || 0;
        document.getElementById('uangBayar').value = (val >= 50000) ? val : current + val;
    }
    hitungKembalian();
}

function hitungKembalian() {
    var bayar    = parseFloat(document.getElementById('uangBayar').value) || 0;
    var total    = parseFloat(document.getElementById('bayarTotal').textContent.replace(/[^0-9]/g,''));
    var kembalian = bayar - total;
    var el  = document.getElementById('kembalianVal');
    var box = document.getElementById('kembalianBox');
    el.textContent = 'Rp ' + Math.max(0, kembalian).toLocaleString('id-ID');
    if (kembalian < 0) {
        box.style.background = 'var(--danger-soft)';
        box.style.borderColor = 'rgba(239,68,68,0.2)';
        el.style.color = 'var(--danger)';
    } else {
        box.style.background = 'var(--success-soft)';
        box.style.borderColor = 'rgba(16,185,129,0.2)';
        el.style.color = 'var(--success)';
    }
}

async function prosesTransaksi() {
    var total  = cart.reduce((s, i) => s + i.qty * i.harga_jual, 0);
    var diskon = parseFloat(document.getElementById('diskonInput').value) || 0;
    var grand  = Math.max(0, total - diskon);
    var bayar  = activeMetode === 'tunai' ? (parseFloat(document.getElementById('uangBayar').value) || 0) : grand;
    var idMem  = document.getElementById('selectMember').value;

    if (activeMetode === 'tunai' && bayar < grand) {
        showToast('error','Kurang Bayar','Uang bayar tidak mencukupi total pembayaran.');
        return;
    }

    var btn = document.getElementById('btnSelesai');
    btn.disabled = true;
    btn.innerHTML = '<span class="loading"></span> Memproses...';

    var res = await fetchJSON('transaksi.php', {
        method: 'POST',
        body: new URLSearchParams({
            action: 'save_transaksi',
            items: JSON.stringify(cart),
            id_member: idMem,
            total: total, diskon: diskon, bayar: bayar,
            metode: activeMetode,
            catatan: document.getElementById('catatanTrx').value
        })
    });

    btn.disabled = false;
    btn.innerHTML = '<i class="fa-solid fa-check-circle"></i> Selesaikan Transaksi';

    if (res && res.success) {
        lastTrxKode = res.kode;
        lastTrxId   = res.id;
        closeModal('modalBayar');
        showStruk(res.kode, total, diskon, grand, bayar, grand > 0 ? bayar - grand : 0);
        loadBarang();
    } else {
        showToast('error','Gagal', res ? res.message : 'Koneksi error');
    }
}

function showStruk(kode, subtotal, diskon, total, bayar, kembalian) {
    var member     = document.getElementById('selectMember');
    var memberNama = member.options[member.selectedIndex].text;
    var now = new Date();
    var tgl = now.toLocaleDateString('id-ID',{weekday:'long',year:'numeric',month:'long',day:'numeric'});
    var jam = now.toLocaleTimeString('id-ID');

    var itemsHtml = cart.map(function(i) {
        return '<div style="display:flex;justify-content:space-between;margin-bottom:4px">' +
            '<div style="flex:1">' +
            '<div style="font-size:12.5px">' + i.nama_barang + '</div>' +
            '<div style="font-size:11px;color:#666">' + i.qty + ' × Rp ' + Number(i.harga_jual).toLocaleString('id-ID') + '</div>' +
            '</div>' +
            '<div style="font-weight:bold;font-size:12.5px">Rp ' + (i.qty*i.harga_jual).toLocaleString('id-ID') + '</div>' +
            '</div>';
    }).join('');

    document.getElementById('modalStrukContent').innerHTML =
        '<div style="padding:20px;font-family:Courier New,monospace;color:#1a1a1a;font-size:13px;line-height:1.6">' +
        '<div style="text-align:center;margin-bottom:16px">' +
        '<div style="font-size:18px;font-weight:900;letter-spacing:2px">MAKASSAR STORE</div>' +
        '<div style="font-size:11px;color:#555">Belanja Mudah, Hidup Berkah — Khas Makassar</div>' +
        '<div style="font-size:11px;color:#555">Jl. Somba Opu No. 88, Makassar 90111</div>' +
        '<div style="margin-top:6px;border-top:1px dashed #ccc;padding-top:6px;font-size:11px;color:#777">' + tgl + ' · ' + jam + '</div>' +
        '</div>' +
        '<div style="margin-bottom:8px;font-size:11px">Kode: <strong>' + kode + '</strong></div>' +
        (member.value ? '<div style="margin-bottom:12px;font-size:11px">Member: <strong>' + memberNama.split('(')[0].trim() + '</strong></div>' : '') +
        '<div style="border-top:1px dashed #ccc;border-bottom:1px dashed #ccc;padding:8px 0;margin-bottom:8px">' + itemsHtml + '</div>' +
        '<div style="display:flex;justify-content:space-between;margin-bottom:2px"><span>Subtotal</span><span>Rp ' + Number(subtotal).toLocaleString('id-ID') + '</span></div>' +
        (diskon > 0 ? '<div style="display:flex;justify-content:space-between;margin-bottom:2px;color:#059669"><span>Diskon</span><span>- Rp ' + Number(diskon).toLocaleString('id-ID') + '</span></div>' : '') +
        '<div style="display:flex;justify-content:space-between;font-size:15px;font-weight:900;border-top:1px dashed #ccc;margin-top:8px;padding-top:8px"><span>TOTAL</span><span>Rp ' + Number(total).toLocaleString('id-ID') + '</span></div>' +
        (bayar > 0 ? '<div style="display:flex;justify-content:space-between;margin-top:6px"><span>Dibayar</span><span>Rp ' + Number(bayar).toLocaleString('id-ID') + '</span></div>' +
            '<div style="display:flex;justify-content:space-between;font-weight:bold"><span>Kembalian</span><span>Rp ' + Number(kembalian).toLocaleString('id-ID') + '</span></div>' : '') +
        '<div style="text-align:center;margin-top:16px;font-size:11px;color:#777;border-top:1px dashed #ccc;padding-top:10px">Terima kasih telah berbelanja!<br>Barang yang sudah dibeli tidak dapat dikembalikan.</div>' +
        '</div>';
    openModal('modalStruk');
}

function cetakStruk() {
    window.open('struk.php?id=' + lastTrxId, '_blank', 'width=400,height=650,toolbar=0,menubar=0');
}

function newTransaction() {
    cart = [];
    document.getElementById('diskonInput').value = 0;
    document.getElementById('selectMember').value = '';
    document.getElementById('catatanTrx').value = '';
    renderCart();
}

// ─── Init & Event Listeners via DOMContentLoaded ───
document.addEventListener('DOMContentLoaded', function() {
    window._catalogMap = {};
    renderCart();
    loadBarang();

    document.getElementById('posSearch').addEventListener('input', debounce(function() { loadBarang(); }, 350));
    document.getElementById('posSearch').addEventListener('keydown', function(e) {
        if (e.key === 'Enter') { e.preventDefault(); loadBarang(); }
    });

    document.getElementById('productGrid').addEventListener('click', function(e) {
        var card = e.target.closest('.product-card');
        if (!card) return;
        var id = card.getAttribute('data-id');
        if (!id || !window._catalogMap[id]) return;
        addToCart(window._catalogMap[id]);
    });
});
</script>

<?php include 'includes/footer.php'; ?>
