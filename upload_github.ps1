# ============================================================
#  MAKASSAR STORE -- Auto Upload to GitHub (with Auto Login)
#  Cara jalankan:
#  1. Buka PowerShell
#  2. Set-ExecutionPolicy RemoteSigned -Scope CurrentUser
#  3. cd "C:\xampp\htdocs\Tugas\kasir"
#  4. .\upload_github.ps1
# ============================================================

Write-Host ""
Write-Host "=========================================" -ForegroundColor Cyan
Write-Host "   MAKASSAR STORE -- Upload ke GitHub    " -ForegroundColor Cyan
Write-Host "=========================================" -ForegroundColor Cyan
Write-Host ""

# ── Cek Git terinstall ──
if (-not (Get-Command git -ErrorAction SilentlyContinue)) {
    Write-Host "[ERROR] Git belum terinstall!" -ForegroundColor Red
    Write-Host "        Download: https://git-scm.com/download/win" -ForegroundColor Yellow
    Read-Host "Tekan Enter untuk keluar"
    exit
}
Write-Host "[OK] Git ditemukan: $(git --version)" -ForegroundColor Green

# ── Input Konfigurasi ──
Write-Host ""
Write-Host "--- Konfigurasi GitHub ---" -ForegroundColor Yellow
$username  = Read-Host "  GitHub username kamu"
$repoName  = Read-Host "  Nama repository (contoh: makassar-store)"
$email     = Read-Host "  Email GitHub kamu"

# ── Input Token ──
Write-Host ""
Write-Host "--- Personal Access Token (PAT) ---" -ForegroundColor Yellow
Write-Host "  Token diperlukan untuk login otomatis ke GitHub." -ForegroundColor White
Write-Host ""
Write-Host "  Cara buat token (kalau belum punya):" -ForegroundColor Cyan
Write-Host "  1. Buka: https://github.com/settings/tokens/new" -ForegroundColor White
Write-Host "  2. Note: makassar-store" -ForegroundColor White
Write-Host "  3. Expiration: 90 days" -ForegroundColor White
Write-Host "  4. Centang: [repo] (full control of private repositories)" -ForegroundColor White
Write-Host "  5. Klik Generate token -- lalu COPY tokennya" -ForegroundColor White
Write-Host ""
$token = Read-Host "  Paste token GitHub kamu di sini"

if ([string]::IsNullOrWhiteSpace($token)) {
    Write-Host "[ERROR] Token tidak boleh kosong!" -ForegroundColor Red
    Read-Host "Tekan Enter untuk keluar"
    exit
}

# ── Input Pesan Commit ──
Write-Host ""
$commitMsg = Read-Host "  Pesan commit (kosongkan untuk pakai default)"
if ([string]::IsNullOrWhiteSpace($commitMsg)) {
    $commitMsg = "Initial commit - Makassar Store POS v3.0"
}

# ── URL dengan token untuk auto-login ──
$repoUrl      = "https://github.com/${username}/${repoName}.git"
$repoUrlToken = "https://${username}:${token}@github.com/${username}/${repoName}.git"

Write-Host ""
Write-Host "--- Informasi Upload ---" -ForegroundColor Yellow
Write-Host "   Username : $username"  -ForegroundColor White
Write-Host "   Repo URL : $repoUrl"   -ForegroundColor White
Write-Host "   Commit   : $commitMsg" -ForegroundColor White
Write-Host "   Login    : via Personal Access Token" -ForegroundColor Green
Write-Host ""

$confirm = Read-Host "Lanjutkan upload? (y/n)"
if ($confirm -ne "y" -and $confirm -ne "Y") {
    Write-Host "Upload dibatalkan." -ForegroundColor Red
    Read-Host "Tekan Enter untuk keluar"
    exit
}

$projectPath = Split-Path -Parent $MyInvocation.MyCommand.Path
Set-Location $projectPath
Write-Host ""
Write-Host "[INFO] Project folder: $projectPath" -ForegroundColor Cyan

# ── Konfigurasi git ──
Write-Host ""
Write-Host "[INFO] Mengatur konfigurasi Git..." -ForegroundColor Yellow
git config --global user.name  $username
git config --global user.email $email
Write-Host "[OK] Konfigurasi Git selesai" -ForegroundColor Green

# ── Buat .gitignore ──
$gitignorePath = Join-Path $projectPath ".gitignore"
if (-not (Test-Path $gitignorePath)) {
    Write-Host "[INFO] Membuat .gitignore..." -ForegroundColor Yellow
    $gitignoreContent = @"
# PHP
*.log
*.cache

# OS
.DS_Store
Thumbs.db
desktop.ini

# Editor
.vscode/
.idea/
*.swp

# Environment
.env
"@
    Set-Content -Path $gitignorePath -Value $gitignoreContent -Encoding UTF8
    Write-Host "[OK] .gitignore dibuat" -ForegroundColor Green
} else {
    Write-Host "[OK] .gitignore sudah ada" -ForegroundColor Green
}

# ── Init Git ──
Write-Host ""
Write-Host "[INFO] Inisialisasi Git repository..." -ForegroundColor Yellow
if (-not (Test-Path (Join-Path $projectPath ".git"))) {
    git init
    Write-Host "[OK] Git diinisialisasi" -ForegroundColor Green
} else {
    Write-Host "[OK] Git sudah ada sebelumnya" -ForegroundColor Green
}

# ── Set branch ke main ──
git branch -M main 2>$null

# ── Set remote dengan token (auto-login) ──
Write-Host ""
Write-Host "[INFO] Menghubungkan ke GitHub dengan token..." -ForegroundColor Yellow
$existingRemote = git remote get-url origin 2>$null
if ($existingRemote) {
    git remote set-url origin $repoUrlToken
} else {
    git remote add origin $repoUrlToken
}
Write-Host "[OK] Remote terhubung: $repoUrl" -ForegroundColor Green

# ── Simpan kredensi agar tidak perlu login ulang ──
Write-Host "[INFO] Menyimpan login ke Credential Manager..." -ForegroundColor Yellow
git config --global credential.helper manager
Write-Host "[OK] Login tersimpan, tidak perlu ulang lagi" -ForegroundColor Green

# ── Stage semua file ──
Write-Host ""
Write-Host "[INFO] Menambahkan semua file ke staging..." -ForegroundColor Yellow
git add .
Write-Host "[OK] Semua file siap di-commit" -ForegroundColor Green

# ── Commit ──
Write-Host ""
Write-Host "[INFO] Commit..." -ForegroundColor Yellow
$commitOutput = git status --short
$fileCount = ($commitOutput | Measure-Object).Count

# Cek apakah ada yang perlu di-commit
$statusCheck = git status --porcelain
if ([string]::IsNullOrWhiteSpace($statusCheck)) {
    Write-Host "[INFO] Tidak ada perubahan baru, menggunakan commit sebelumnya..." -ForegroundColor Yellow
} else {
    git commit -m $commitMsg
    Write-Host "[OK] Commit berhasil" -ForegroundColor Green
}

# ── Push ──
Write-Host ""
Write-Host "[INFO] Mengupload ke GitHub (auto login dengan token)..." -ForegroundColor Yellow
Write-Host ""
git push -u origin main

if ($LASTEXITCODE -eq 0) {
    Write-Host ""
    Write-Host "=========================================" -ForegroundColor Green
    Write-Host "   [SUKSES] UPLOAD BERHASIL!             " -ForegroundColor Green
    Write-Host "=========================================" -ForegroundColor Green
    Write-Host ""
    Write-Host "   Repository kamu:" -ForegroundColor White
    Write-Host "   https://github.com/$username/$repoName" -ForegroundColor Cyan
    Write-Host ""
    Write-Host "   README dengan foto sudah tampil di GitHub!" -ForegroundColor Green

    # ── Hapus token dari remote URL setelah push (keamanan) ──
    git remote set-url origin $repoUrl
    Write-Host "[OK] Token dibersihkan dari remote URL (aman)" -ForegroundColor Green

} else {
    Write-Host ""
    Write-Host "=========================================" -ForegroundColor Red
    Write-Host "   [GAGAL] UPLOAD TIDAK BERHASIL         " -ForegroundColor Red
    Write-Host "=========================================" -ForegroundColor Red
    Write-Host ""
    Write-Host "   Kemungkinan penyebab:" -ForegroundColor Yellow
    Write-Host "   1. Repository belum dibuat di GitHub" -ForegroundColor White
    Write-Host "      Buat dulu di: https://github.com/new" -ForegroundColor Cyan
    Write-Host "      Nama: $repoName | Pilih Public | JANGAN centang Add README" -ForegroundColor White
    Write-Host ""
    Write-Host "   2. Token salah atau sudah kadaluarsa" -ForegroundColor White
    Write-Host "      Buat token baru di: https://github.com/settings/tokens/new" -ForegroundColor Cyan
    Write-Host ""
    Write-Host "   3. Username salah (pastikan sama persis dengan GitHub)" -ForegroundColor White
    Write-Host ""

    # Hapus token dari remote URL
    git remote set-url origin $repoUrl 2>$null
}

Write-Host ""
Read-Host "Tekan Enter untuk menutup"
