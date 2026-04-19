<?php
// ============================================================
// LOGIN PAGE — MakassaStore POS
// ============================================================
if (session_status() === PHP_SESSION_NONE) session_start();

// Sudah login → redirect
if (!empty($_SESSION['kasir_id'])) {
    header('Location: dashboard.php'); exit;
}

require_once 'config/database.php';
$db = getDB();
$error = '';
$success = '';
$mode = $_GET['mode'] ?? 'login'; // login | register

if (isset($_GET['logout'])) $success = 'Berhasil logout. Sampai jumpa! 👋';

// ── Handle Login ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_action'] ?? '') === 'login') {
    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    if ($username && $password) {
        $uQ = $db->real_escape_string($username);
        $r  = $db->query("SELECT * FROM tbl_users WHERE (username='$uQ' OR email='$uQ') AND status='aktif' LIMIT 1");
        $user = $r ? $r->fetch_assoc() : null;
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['kasir_id']   = $user['id'];
            $_SESSION['kasir_nama'] = $user['nama'];
            $_SESSION['kasir_user'] = $user['username'];
            $_SESSION['kasir_role'] = $user['role'];
            $db->query("UPDATE tbl_users SET last_login=NOW() WHERE id={$user['id']}");
            header('Location: dashboard.php'); exit;
        } else {
            $error = 'Username atau password salah!';
        }
    } else {
        $error = 'Harap isi semua kolom.';
    }
}

// ── Handle Register ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_action'] ?? '') === 'register') {
    $nama     = sanitize($_POST['nama'] ?? '');
    $username = sanitize($_POST['username'] ?? '');
    $email    = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';
    $kode_reg = sanitize($_POST['kode_registrasi'] ?? '');

    if (!$nama || !$username || !$password || !$confirm) {
        $error = 'Harap isi semua kolom wajib.';
    } elseif ($password !== $confirm) {
        $error = 'Password tidak cocok!';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter.';
    } elseif ($kode_reg !== REGISTER_CODE) {
        $error = 'Kode registrasi tidak valid!';
    } else {
        $uQ = $db->real_escape_string($username);
        $eQ = $db->real_escape_string($email);
        $exists = $db->query("SELECT id FROM tbl_users WHERE username='$uQ' OR email='$eQ' LIMIT 1")->fetch_assoc();
        if ($exists) {
            $error = 'Username atau email sudah digunakan!';
        } else {
            $hash  = password_hash($password, PASSWORD_BCRYPT);
            $nQ    = $db->real_escape_string($nama);
            $pQ    = $db->real_escape_string($hash);
            $role  = 'kasir';
            $db->query("INSERT INTO tbl_users(nama,username,email,password,role,status) VALUES('$nQ','$uQ','$eQ','$pQ','$role','aktif')");
            if ($db->insert_id) {
                $success = 'Akun berhasil dibuat! Silakan login.';
                $mode = 'login';
            } else {
                $error = 'Gagal membuat akun: ' . $db->error;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — <?= APP_NAME ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800;900&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    :root {
        --primary:    #6366f1;
        --primary-h:  #4f46e5;
        --amber:      #f59e0b;
        --success:    #10b981;
        --danger:     #ef4444;
        --bg:         #060b18;
        --bg-card:    #0d1629;
        --border:     rgba(255,255,255,0.08);
        --text:       #f1f5f9;
        --text-sec:   #94a3b8;
        --text-muted: #475569;
    }

    body {
        font-family: 'Plus Jakarta Sans', sans-serif;
        background: var(--bg);
        color: var(--text);
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        position: relative;
    }

    /* ── Animated BG ── */
    .bg-orbs {
        position: fixed; inset: 0; pointer-events: none; z-index: 0;
    }
    .orb {
        position: absolute;
        border-radius: 50%;
        filter: blur(80px);
        opacity: 0.18;
        animation: floatOrb linear infinite;
    }
    .orb-1 { width: 600px; height: 600px; background: #6366f1; top: -200px; left: -150px; animation-duration: 20s; }
    .orb-2 { width: 500px; height: 500px; background: #f59e0b; bottom: -150px; right: -100px; animation-duration: 25s; animation-delay: -8s; }
    .orb-3 { width: 350px; height: 350px; background: #10b981; top: 40%; left: 50%; animation-duration: 18s; animation-delay: -5s; }

    @keyframes floatOrb {
        0%   { transform: translate(0,0) scale(1); }
        33%  { transform: translate(30px,-20px) scale(1.05); }
        66%  { transform: translate(-20px,30px) scale(0.95); }
        100% { transform: translate(0,0) scale(1); }
    }

    /* Grid dots */
    .bg-grid {
        position: fixed; inset: 0; z-index: 0;
        background-image: radial-gradient(circle, rgba(99,102,241,0.08) 1px, transparent 1px);
        background-size: 40px 40px;
    }

    /* ── Login Wrapper ── */
    .login-wrap {
        position: relative; z-index: 1;
        width: 100%; max-width: 460px;
        padding: 24px;
        animation: slideUp 0.5s cubic-bezier(0.34, 1.56, 0.64, 1) both;
    }

    @keyframes slideUp {
        from { opacity: 0; transform: translateY(40px) scale(0.95); }
        to   { opacity: 1; transform: translateY(0) scale(1); }
    }

    /* ── Brand ── */
    .login-brand {
        text-align: center;
        margin-bottom: 28px;
    }
    .brand-logo {
        width: 72px; height: 72px;
        background: linear-gradient(135deg, #6366f1, #a78bfa);
        border-radius: 20px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 32px;
        margin-bottom: 14px;
        box-shadow: 0 8px 32px rgba(99,102,241,0.4);
        animation: pulseLogo 3s ease-in-out infinite;
    }
    @keyframes pulseLogo {
        0%,100% { box-shadow: 0 8px 32px rgba(99,102,241,0.4); }
        50%      { box-shadow: 0 8px 48px rgba(99,102,241,0.6); }
    }
    .brand-name {
        font-size: 26px; font-weight: 900;
        background: linear-gradient(135deg, #f1f5f9, #6366f1);
        -webkit-background-clip: text; -webkit-text-fill-color: transparent;
        letter-spacing: -0.5px;
    }
    .brand-sub { font-size: 12px; color: var(--text-muted); margin-top: 4px; letter-spacing: 1px; text-transform: uppercase; }

    /* ── Card ── */
    .login-card {
        background: rgba(13,22,41,0.85);
        border: 1px solid var(--border);
        border-radius: 24px;
        padding: 32px 28px;
        backdrop-filter: blur(20px);
        box-shadow: 0 24px 80px rgba(0,0,0,0.5);
    }

    /* ── Tabs ── */
    .auth-tabs {
        display: grid;
        grid-template-columns: 1fr 1fr;
        background: rgba(255,255,255,0.04);
        border-radius: 12px;
        padding: 4px;
        margin-bottom: 24px;
        border: 1px solid var(--border);
    }
    .auth-tab {
        padding: 9px;
        text-align: center;
        font-size: 13px;
        font-weight: 600;
        color: var(--text-muted);
        border-radius: 9px;
        cursor: pointer;
        transition: all 0.2s ease;
        border: none;
        background: none;
        font-family: inherit;
    }
    .auth-tab.active {
        background: var(--primary);
        color: white;
        box-shadow: 0 4px 14px rgba(99,102,241,0.35);
    }

    /* ── Form ── */
    .form-group { margin-bottom: 16px; }
    .form-label {
        display: block;
        font-size: 12px; font-weight: 600;
        color: var(--text-sec);
        margin-bottom: 7px;
        letter-spacing: 0.3px;
    }
    .input-wrap { position: relative; }
    .input-wrap i {
        position: absolute; left: 14px; top: 50%; transform: translateY(-50%);
        color: var(--text-muted); font-size: 14px;
        transition: color 0.2s;
    }
    .input-wrap input:focus + i,
    .input-wrap:focus-within i { color: var(--primary); }
    .form-input {
        width: 100%;
        background: rgba(255,255,255,0.05);
        border: 1.5px solid var(--border);
        border-radius: 10px;
        color: var(--text);
        padding: 11px 14px 11px 42px;
        font-size: 14px;
        font-family: inherit;
        outline: none;
        transition: all 0.2s ease;
    }
    .form-input:focus {
        border-color: var(--primary);
        background: rgba(99,102,241,0.06);
        box-shadow: 0 0 0 3px rgba(99,102,241,0.12);
    }
    .form-input::placeholder { color: var(--text-muted); }

    /* Password toggle */
    .pass-toggle {
        position: absolute; right: 14px; top: 50%; transform: translateY(-50%);
        color: var(--text-muted); cursor: pointer; font-size: 14px;
        transition: color 0.2s; left: auto;
    }
    .pass-toggle:hover { color: var(--primary); }

    /* ── Alert ── */
    .alert {
        padding: 12px 14px;
        border-radius: 10px;
        font-size: 13px;
        margin-bottom: 16px;
        display: flex;
        align-items: center;
        gap: 10px;
        animation: fadeIn 0.3s ease;
    }
    @keyframes fadeIn { from { opacity:0; transform:translateY(-6px);} to { opacity:1; transform:translateY(0);} }
    .alert-error   { background: rgba(239,68,68,0.12); border: 1px solid rgba(239,68,68,0.2); color: #fca5a5; }
    .alert-success { background: rgba(16,185,129,0.12); border: 1px solid rgba(16,185,129,0.2); color: #6ee7b7; }

    /* ── Button ── */
    .btn-login {
        width: 100%;
        padding: 13px;
        background: linear-gradient(135deg, #6366f1, #818cf8);
        color: white;
        border: none;
        border-radius: 10px;
        font-size: 14px;
        font-weight: 700;
        font-family: inherit;
        cursor: pointer;
        margin-top: 6px;
        transition: all 0.2s ease;
        box-shadow: 0 6px 20px rgba(99,102,241,0.35);
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        position: relative;
        overflow: hidden;
    }
    .btn-login::after {
        content: '';
        position: absolute; inset: 0;
        background: white;
        opacity: 0;
        transition: opacity 0.15s;
    }
    .btn-login:hover { transform: translateY(-2px); box-shadow: 0 10px 28px rgba(99,102,241,0.45); }
    .btn-login:active { transform: translateY(0); }
    .btn-login:active::after { opacity: 0.08; }
    .btn-login:disabled { opacity: 0.7; cursor: wait; transform: none; }

    /* ── Extras ── */
    .kode-hint {
        font-size: 11px; color: var(--text-muted);
        margin-top: 4px; display: flex; align-items: center; gap: 4px;
    }
    .footer-note {
        text-align: center;
        font-size: 11.5px;
        color: var(--text-muted);
        margin-top: 20px;
    }
    .footer-note span { color: var(--primary); }

    /* Floating particles */
    .particles { position: fixed; inset: 0; z-index: 0; pointer-events: none; }
    .particle {
        position: absolute;
        width: 3px; height: 3px;
        background: rgba(99,102,241,0.5);
        border-radius: 50%;
        animation: drift linear infinite;
    }
    @keyframes drift {
        from { transform: translateY(100vh) rotate(0deg); opacity: 0; }
        10%  { opacity: 1; }
        90%  { opacity: 1; }
        to   { transform: translateY(-100px) rotate(360deg); opacity: 0; }
    }

    /* Form panels */
    .form-panel { display: none; }
    .form-panel.active { display: block; animation: panelIn 0.3s ease both; }
    @keyframes panelIn { from { opacity:0; transform: translateX(10px);} to {opacity:1;transform:translateX(0);} }
    </style>
</head>
<body>
    <div class="bg-orbs">
        <div class="orb orb-1"></div>
        <div class="orb orb-2"></div>
        <div class="orb orb-3"></div>
    </div>
    <div class="bg-grid"></div>
    <div class="particles" id="particles"></div>

    <div class="login-wrap">
        <!-- Brand -->
        <div class="login-brand">
            <div class="brand-logo">🏬</div>
            <div class="brand-name"><?= APP_NAME ?></div>
            <div class="brand-sub"><?= APP_TAGLINE ?></div>
        </div>

        <!-- Card -->
        <div class="login-card">
            <!-- Tabs -->
            <div class="auth-tabs">
                <button class="auth-tab <?= $mode==='login'?'active':'' ?>" onclick="switchMode('login')" id="tab-login">
                    <i class="fa-solid fa-right-to-bracket"></i> Login
                </button>
                <button class="auth-tab <?= $mode==='register'?'active':'' ?>" onclick="switchMode('register')" id="tab-register">
                    <i class="fa-solid fa-user-plus"></i> Register
                </button>
            </div>

            <!-- Alerts -->
            <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fa-solid fa-circle-exclamation"></i> <?= $error ?>
            </div>
            <?php endif; ?>
            <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fa-solid fa-circle-check"></i> <?= $success ?>
            </div>
            <?php endif; ?>

            <!-- ── LOGIN FORM ── -->
            <form method="POST" id="form-login" class="form-panel <?= $mode==='login'?'active':'' ?>" onsubmit="handleSubmit(this)">
                <input type="hidden" name="form_action" value="login">

                <div class="form-group">
                    <label class="form-label">Username atau Email</label>
                    <div class="input-wrap">
                        <input type="text" name="username" class="form-input" placeholder="Masukkan username..." required autocomplete="username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                        <i class="fa-solid fa-user"></i>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Password</label>
                    <div class="input-wrap">
                        <input type="password" name="password" id="pw-login" class="form-input" placeholder="••••••••" required autocomplete="current-password">
                        <i class="fa-solid fa-lock"></i>
                        <i class="fa-solid fa-eye pass-toggle" onclick="togglePw('pw-login', this)" title="Tampilkan password"></i>
                    </div>
                </div>

                <button type="submit" class="btn-login" id="btn-login">
                    <i class="fa-solid fa-right-to-bracket"></i> Masuk ke Sistem
                </button>
            </form>

            <!-- ── REGISTER FORM ── -->
            <form method="POST" id="form-register" class="form-panel <?= $mode==='register'?'active':'' ?>" onsubmit="handleSubmit(this)">
                <input type="hidden" name="form_action" value="register">

                <div class="form-group">
                    <label class="form-label">Nama Lengkap <span style="color:#ef4444">*</span></label>
                    <div class="input-wrap">
                        <input type="text" name="nama" class="form-input" placeholder="Nama kasir..." required value="<?= htmlspecialchars($_POST['nama'] ?? '') ?>">
                        <i class="fa-solid fa-id-card"></i>
                    </div>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                    <div class="form-group" style="margin:0">
                        <label class="form-label">Username <span style="color:#ef4444">*</span></label>
                        <div class="input-wrap">
                            <input type="text" name="username" class="form-input" placeholder="username" required value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                            <i class="fa-solid fa-at"></i>
                        </div>
                    </div>
                    <div class="form-group" style="margin:0">
                        <label class="form-label">Email</label>
                        <div class="input-wrap">
                            <input type="email" name="email" class="form-input" placeholder="email@..." value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                            <i class="fa-solid fa-envelope"></i>
                        </div>
                    </div>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:16px">
                    <div class="form-group" style="margin:0">
                        <label class="form-label">Password <span style="color:#ef4444">*</span></label>
                        <div class="input-wrap">
                            <input type="password" name="password" id="pw-reg" class="form-input" placeholder="••••••" required>
                            <i class="fa-solid fa-lock"></i>
                            <i class="fa-solid fa-eye pass-toggle" onclick="togglePw('pw-reg', this)"></i>
                        </div>
                    </div>
                    <div class="form-group" style="margin:0">
                        <label class="form-label">Konfirmasi <span style="color:#ef4444">*</span></label>
                        <div class="input-wrap">
                            <input type="password" name="confirm_password" id="pw-conf" class="form-input" placeholder="••••••" required>
                            <i class="fa-solid fa-lock"></i>
                            <i class="fa-solid fa-eye pass-toggle" onclick="togglePw('pw-conf', this)"></i>
                        </div>
                    </div>
                </div>

                <div class="form-group" style="margin-top:16px">
                    <label class="form-label">Kode Registrasi <span style="color:#ef4444">*</span></label>
                    <div class="input-wrap">
                        <input type="text" name="kode_registrasi" class="form-input" placeholder="Masukkan kode dari admin..." required>
                        <i class="fa-solid fa-key"></i>
                    </div>
                    <div class="kode-hint"><i class="fa-solid fa-circle-info"></i> Minta kode registrasi kepada Admin</div>
                </div>

                <button type="submit" class="btn-login" id="btn-register" style="background:linear-gradient(135deg,#10b981,#34d399);box-shadow:0 6px 20px rgba(16,185,129,0.35)">
                    <i class="fa-solid fa-user-plus"></i> Buat Akun
                </button>
            </form>
        </div>

        <div class="footer-note">
            &copy; <?= date('Y') ?> <span><?= APP_NAME ?></span> · Point of Sale System
        </div>
    </div>

    <script>
    // ── Mode switcher ──
    function switchMode(mode) {
        document.getElementById('form-login').classList.toggle('active', mode === 'login');
        document.getElementById('form-register').classList.toggle('active', mode === 'register');
        document.getElementById('tab-login').classList.toggle('active', mode === 'login');
        document.getElementById('tab-register').classList.toggle('active', mode === 'register');
    }

    // ── Toggle password visibility ──
    function togglePw(id, icon) {
        const inp = document.getElementById(id);
        const show = inp.type === 'password';
        inp.type = show ? 'text' : 'password';
        icon.className = `fa-solid fa-${show ? 'eye-slash' : 'eye'} pass-toggle`;
    }

    // ── Submit button loading state ──
    function handleSubmit(form) {
        const btn = form.querySelector('button[type=submit]');
        btn.disabled = true;
        btn.innerHTML = '<span style="display:inline-block;width:16px;height:16px;border:2px solid rgba(255,255,255,0.3);border-top-color:white;border-radius:50%;animation:spin .6s linear infinite;vertical-align:middle"></span> Memproses...';
        setTimeout(() => { btn.disabled = false; }, 4000);
    }

    // ── Floating particles ──
    const container = document.getElementById('particles');
    for (let i = 0; i < 18; i++) {
        const p = document.createElement('div');
        p.className = 'particle';
        p.style.cssText = `
            left: ${Math.random()*100}%;
            width: ${Math.random()*3+2}px;
            height: ${Math.random()*3+2}px;
            animation-duration: ${Math.random()*12+8}s;
            animation-delay: ${Math.random()*-15}s;
            background: hsl(${Math.random()*60+220}, 70%, 65%);
            opacity: ${Math.random()*0.4+0.1};
        `;
        container.appendChild(p);
    }

    // spin keyframe injection
    const st = document.createElement('style');
    st.textContent = '@keyframes spin { to { transform: rotate(360deg); } }';
    document.head.appendChild(st);
    </script>
</body>
</html>
