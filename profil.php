<?php
// ============================================================
// PROFIL USER — Ganti Password / Username / Nama
// ============================================================
require_once 'config/database.php';
$db = getDB();

if (session_status() === PHP_SESSION_NONE) session_start();
require_once 'includes/auth.php';
requireLogin();

$currentUser = getCurrentUser();
$userId = $currentUser['id'];

$errors   = [];
$success  = '';
$tabActive = $_GET['tab'] ?? 'profil';

// ── Ambil data user terbaru dari DB ──
$userRow = $db->query("SELECT * FROM tbl_users WHERE id = $userId")->fetch_assoc();

// ── Handle POST ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // ─ Update Profil ─
    if ($action === 'update_profil') {
        $nama     = sanitize($_POST['nama'] ?? '');
        $username = sanitize($_POST['username'] ?? '');
        $email    = sanitize($_POST['email'] ?? '');

        if (strlen($nama) < 2)     $errors[] = 'Nama minimal 2 karakter.';
        if (strlen($username) < 3) $errors[] = 'Username minimal 3 karakter.';
        if (!preg_match('/^[a-zA-Z0-9_\.]+$/', $username)) $errors[] = 'Username hanya boleh huruf, angka, titik, atau underscore.';

        // Cek username unik (kecuali diri sendiri)
        $check = $db->query("SELECT id FROM tbl_users WHERE username='$username' AND id != $userId")->fetch_assoc();
        if ($check) $errors[] = 'Username sudah dipakai pengguna lain.';

        if (empty($errors)) {
            $db->query("UPDATE tbl_users SET nama='$nama', username='$username', email='$email' WHERE id=$userId");
            // Update session
            $_SESSION['kasir_nama'] = $nama;
            $_SESSION['kasir_user'] = $username;
            $success = 'Profil berhasil diperbarui!';
            $tabActive = 'profil';
            $userRow = $db->query("SELECT * FROM tbl_users WHERE id = $userId")->fetch_assoc();
        }
    }

    // ─ Ganti Password ─
    if ($action === 'ganti_password') {
        $pw_lama  = $_POST['pw_lama']   ?? '';
        $pw_baru  = $_POST['pw_baru']   ?? '';
        $pw_ulang = $_POST['pw_ulang']  ?? '';

        if (!password_verify($pw_lama, $userRow['password'])) $errors[] = 'Password lama tidak sesuai.';
        if (strlen($pw_baru) < 6)  $errors[] = 'Password baru minimal 6 karakter.';
        if ($pw_baru !== $pw_ulang) $errors[] = 'Konfirmasi password tidak cocok.';

        if (empty($errors)) {
            $hash = password_hash($pw_baru, PASSWORD_BCRYPT);
            $db->query("UPDATE tbl_users SET password='$hash' WHERE id=$userId");
            $success = 'Password berhasil diubah! Silakan login ulang dengan password baru.';
            $tabActive = 'password';
        }
    }
}

$currentPage = 'profil';
$pageTitle   = 'Profil Saya';
$pageSub     = 'Pengaturan Akun Pengguna';
include 'includes/header.php';
?>

<style>
.profil-wrap {
    max-width: 640px;
    margin: 0 auto;
}
.profil-tabs {
    display: flex;
    gap: 4px;
    margin-bottom: 20px;
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 5px;
}
.profil-tab {
    flex: 1;
    text-align: center;
    padding: 9px 16px;
    font-size: 13px;
    font-weight: 600;
    color: var(--text-muted);
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s ease;
    text-decoration: none;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}
.profil-tab:hover { color: var(--text); background: var(--bg-hover); }
.profil-tab.active { background: var(--primary); color: #fff; box-shadow: 0 2px 10px rgba(99,102,241,0.4); }

.avatar-big {
    width: 72px; height: 72px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--primary), #a78bfa);
    display: flex; align-items: center; justify-content: center;
    font-size: 30px; font-weight: 800; color: white;
    border: 3px solid rgba(99,102,241,0.3);
    box-shadow: 0 0 0 6px rgba(99,102,241,0.08);
    flex-shrink: 0;
}
.profil-hero {
    display: flex;
    align-items: center;
    gap: 18px;
    padding: 20px;
    background: linear-gradient(135deg, rgba(99,102,241,0.08), rgba(167,139,250,0.05));
    border: 1px solid rgba(99,102,241,0.15);
    border-radius: 14px;
    margin-bottom: 22px;
}
.strength-bar {
    height: 5px;
    border-radius: 99px;
    background: var(--bg-hover);
    margin-top: 6px;
    overflow: hidden;
}
.strength-fill {
    height: 100%;
    border-radius: 99px;
    transition: width 0.4s ease, background 0.4s ease;
}
</style>

<div class="profil-wrap">

    <?php if ($success): ?>
    <div class="alert alert-success" style="background:var(--success-soft);border:1px solid rgba(16,185,129,0.2);border-radius:10px;padding:12px 16px;color:var(--success);font-size:13.5px;display:flex;align-items:center;gap:10px;margin-bottom:16px">
        <i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($success) ?>
    </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
    <div class="alert alert-error" style="background:var(--danger-soft);border:1px solid rgba(239,68,68,0.2);border-radius:10px;padding:12px 16px;color:var(--danger);font-size:13.5px;margin-bottom:16px">
        <i class="fa-solid fa-circle-xmark"></i>
        <ul style="margin:6px 0 0 18px;padding:0">
            <?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <!-- Hero Card -->
    <div class="profil-hero">
        <div class="avatar-big"><?= strtoupper(substr($userRow['nama'], 0, 1)) ?></div>
        <div>
            <div style="font-size:18px;font-weight:800;color:var(--text)"><?= htmlspecialchars($userRow['nama']) ?></div>
            <div style="font-size:13px;color:var(--text-muted);margin-top:2px">@<?= htmlspecialchars($userRow['username']) ?></div>
            <div style="margin-top:8px;display:flex;gap:8px">
                <span class="badge <?= $userRow['role'] === 'admin' ? 'badge-primary' : 'badge-success' ?>">
                    <i class="fa-solid <?= $userRow['role'] === 'admin' ? 'fa-shield-halved' : 'fa-user' ?>"></i>
                    <?= ucfirst($userRow['role']) ?>
                </span>
                <span class="badge badge-neutral"><i class="fa-solid fa-circle-check"></i> <?= ucfirst($userRow['status']) ?></span>
            </div>
        </div>
    </div>

    <!-- Tabs -->
    <div class="profil-tabs">
        <a href="profil.php?tab=profil" class="profil-tab <?= $tabActive==='profil'?'active':'' ?>">
            <i class="fa-solid fa-user-pen"></i> Edit Profil
        </a>
        <a href="profil.php?tab=password" class="profil-tab <?= $tabActive==='password'?'active':'' ?>">
            <i class="fa-solid fa-lock"></i> Ganti Password
        </a>
    </div>

    <!-- Tab: Edit Profil -->
    <?php if ($tabActive === 'profil'): ?>
    <div class="card">
        <div class="card-header">
            <i class="fa-solid fa-user-pen" style="color:var(--primary)"></i>
            <h3>Informasi Profil</h3>
        </div>
        <div class="card-body">
            <form method="POST" action="profil.php?tab=profil">
                <input type="hidden" name="action" value="update_profil">

                <div class="form-group">
                    <label class="form-label">Nama Lengkap <span style="color:var(--danger)">*</span></label>
                    <div class="input-group prefix">
                        <span class="input-prefix"><i class="fa-solid fa-id-card"></i></span>
                        <input type="text" name="nama" class="form-control"
                               value="<?= htmlspecialchars($userRow['nama']) ?>"
                               placeholder="Nama lengkap Anda" required minlength="2">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Username <span style="color:var(--danger)">*</span></label>
                    <div class="input-group prefix">
                        <span class="input-prefix"><i class="fa-solid fa-at"></i></span>
                        <input type="text" name="username" class="form-control"
                               value="<?= htmlspecialchars($userRow['username']) ?>"
                               placeholder="username_anda" required minlength="3"
                               pattern="[a-zA-Z0-9_\.]+">
                    </div>
                    <div style="font-size:11.5px;color:var(--text-muted);margin-top:5px">
                        <i class="fa-solid fa-circle-info"></i> Hanya huruf, angka, titik (.), dan underscore (_)
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Email</label>
                    <div class="input-group prefix">
                        <span class="input-prefix"><i class="fa-solid fa-envelope"></i></span>
                        <input type="email" name="email" class="form-control"
                               value="<?= htmlspecialchars($userRow['email'] ?? '') ?>"
                               placeholder="email@contoh.com">
                    </div>
                </div>

                <div class="form-group" style="margin-bottom:0">
                    <label class="form-label">Role</label>
                    <div class="input-group prefix">
                        <span class="input-prefix"><i class="fa-solid fa-shield-halved"></i></span>
                        <input type="text" class="form-control" value="<?= ucfirst($userRow['role']) ?>" disabled
                               style="opacity:0.6;cursor:not-allowed">
                    </div>
                    <div style="font-size:11.5px;color:var(--text-muted);margin-top:5px">
                        <i class="fa-solid fa-lock"></i> Role hanya dapat diubah oleh Administrator
                    </div>
                </div>

                <div style="margin-top:20px;display:flex;gap:10px">
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-floppy-disk"></i> Simpan Perubahan
                    </button>
                    <a href="dashboard.php" class="btn btn-ghost">Batal</a>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- Tab: Ganti Password -->
    <?php if ($tabActive === 'password'): ?>
    <div class="card">
        <div class="card-header">
            <i class="fa-solid fa-lock" style="color:var(--amber)"></i>
            <h3>Ganti Password</h3>
        </div>
        <div class="card-body">
            <form method="POST" action="profil.php?tab=password" id="formPassword">
                <input type="hidden" name="action" value="ganti_password">

                <div class="form-group">
                    <label class="form-label">Password Lama <span style="color:var(--danger)">*</span></label>
                    <div class="input-group prefix" style="position:relative">
                        <span class="input-prefix"><i class="fa-solid fa-key"></i></span>
                        <input type="password" name="pw_lama" id="pw_lama" class="form-control"
                               placeholder="Masukkan password lama" required autocomplete="current-password">
                        <button type="button" onclick="togglePw('pw_lama',this)"
                                style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--text-muted);cursor:pointer;font-size:14px">
                            <i class="fa-solid fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Password Baru <span style="color:var(--danger)">*</span></label>
                    <div class="input-group prefix" style="position:relative">
                        <span class="input-prefix"><i class="fa-solid fa-lock"></i></span>
                        <input type="password" name="pw_baru" id="pw_baru" class="form-control"
                               placeholder="Min. 6 karakter" required minlength="6"
                               autocomplete="new-password" oninput="checkStrength(this.value)">
                        <button type="button" onclick="togglePw('pw_baru',this)"
                                style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--text-muted);cursor:pointer;font-size:14px">
                            <i class="fa-solid fa-eye"></i>
                        </button>
                    </div>
                    <!-- Strength bar -->
                    <div class="strength-bar">
                        <div class="strength-fill" id="strengthFill" style="width:0%;background:var(--danger)"></div>
                    </div>
                    <div style="font-size:11px;color:var(--text-muted);margin-top:3px" id="strengthLabel">Masukkan password baru</div>
                </div>

                <div class="form-group" style="margin-bottom:0">
                    <label class="form-label">Konfirmasi Password Baru <span style="color:var(--danger)">*</span></label>
                    <div class="input-group prefix" style="position:relative">
                        <span class="input-prefix"><i class="fa-solid fa-shield-check"></i></span>
                        <input type="password" name="pw_ulang" id="pw_ulang" class="form-control"
                               placeholder="Ulangi password baru" required autocomplete="new-password"
                               oninput="checkMatch()">
                        <button type="button" onclick="togglePw('pw_ulang',this)"
                                style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--text-muted);cursor:pointer;font-size:14px">
                            <i class="fa-solid fa-eye"></i>
                        </button>
                    </div>
                    <div style="font-size:11px;margin-top:4px" id="matchLabel"></div>
                </div>

                <div style="margin-top:20px;display:flex;gap:10px">
                    <button type="submit" class="btn btn-warning" id="btnSavePass">
                        <i class="fa-solid fa-shield-halved"></i> Ubah Password
                    </button>
                    <a href="profil.php?tab=profil" class="btn btn-ghost">Batal</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Info card -->
    <div class="card" style="margin-top:14px;border-color:rgba(245,158,11,0.2)">
        <div class="card-body" style="display:flex;gap:14px;align-items:flex-start;padding:14px 16px">
            <div style="font-size:22px;margin-top:2px">💡</div>
            <div style="font-size:13px;color:var(--text-muted);line-height:1.7">
                <strong style="color:var(--text)">Tips keamanan password:</strong><br>
                Gunakan kombinasi huruf besar, huruf kecil, angka, dan simbol. Minimal 8 karakter untuk keamanan optimal. Jangan gunakan tanggal lahir atau nama sendiri.
            </div>
        </div>
    </div>
    <?php endif; ?>

</div>

<script>
function togglePw(id, btn) {
    var inp = document.getElementById(id);
    var icon = btn.querySelector('i');
    if (inp.type === 'password') {
        inp.type = 'text';
        icon.classList.replace('fa-eye','fa-eye-slash');
    } else {
        inp.type = 'password';
        icon.classList.replace('fa-eye-slash','fa-eye');
    }
}

function checkStrength(val) {
    var fill  = document.getElementById('strengthFill');
    var label = document.getElementById('strengthLabel');
    if (!fill) return;
    var score = 0;
    if (val.length >= 6)  score++;
    if (val.length >= 10) score++;
    if (/[A-Z]/.test(val)) score++;
    if (/[0-9]/.test(val)) score++;
    if (/[^A-Za-z0-9]/.test(val)) score++;
    var pct   = (score / 5) * 100;
    var color = score <= 1 ? '#ef4444' : score <= 2 ? '#f59e0b' : score <= 3 ? '#3b82f6' : '#10b981';
    var text  = score <= 1 ? '😟 Sangat lemah' : score <= 2 ? '😐 Lemah' : score <= 3 ? '🙂 Cukup' : score <= 4 ? '😊 Kuat' : '💪 Sangat kuat!';
    fill.style.width = pct + '%';
    fill.style.background = color;
    label.textContent = text;
    label.style.color = color;
}

function checkMatch() {
    var pw   = document.getElementById('pw_baru')?.value || '';
    var conf = document.getElementById('pw_ulang')?.value || '';
    var lbl  = document.getElementById('matchLabel');
    if (!lbl || conf.length === 0) return;
    if (pw === conf) {
        lbl.textContent = '✅ Password cocok';
        lbl.style.color = 'var(--success)';
    } else {
        lbl.textContent = '❌ Password tidak cocok';
        lbl.style.color = 'var(--danger)';
    }
}
</script>

<?php include 'includes/footer.php'; ?>
