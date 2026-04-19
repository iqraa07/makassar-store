# ============================================================
#  MAKASSAR STORE -- Auto Upload to GitHub
#  Cara: buka PowerShell, jalankan:
#  Set-ExecutionPolicy RemoteSigned -Scope CurrentUser
#  lalu: .\upload_github.ps1
# ============================================================

Write-Host ""
Write-Host "=========================================" -ForegroundColor Cyan
Write-Host "   MAKASSAR STORE -- Upload ke GitHub    " -ForegroundColor Cyan
Write-Host "=========================================" -ForegroundColor Cyan
Write-Host ""

# -- Cek Git terinstall --
if (-not (Get-Command git -ErrorAction SilentlyContinue)) {
    Write-Host "[ERROR] Git belum terinstall!" -ForegroundColor Red
    Write-Host "        Download di: https://git-scm.com/download/win" -ForegroundColor Yellow
    Write-Host "        Setelah install Git, jalankan script ini lagi." -ForegroundColor Yellow
    Read-Host "Tekan Enter untuk keluar"
    exit
}
Write-Host "[OK] Git ditemukan: $(git --version)" -ForegroundColor Green

# -- Input dari user --
Write-Host ""
Write-Host "--- Konfigurasi GitHub ---" -ForegroundColor Yellow
$username  = Read-Host "  Masukkan GitHub username kamu"
$repoName  = Read-Host "  Masukkan nama repository (contoh: makassar-store)"
$email     = Read-Host "  Masukkan email GitHub kamu"
$commitMsg = Read-Host "  Pesan commit (kosongkan untuk pakai default)"

if ([string]::IsNullOrWhiteSpace($commitMsg)) {
    $commitMsg = "Initial commit - Makassar Store POS v3.0"
}

$repoUrl = "https://github.com/$username/$repoName.git"

Write-Host ""
Write-Host "--- Informasi Upload ---" -ForegroundColor Yellow
Write-Host "   Username : $username"  -ForegroundColor White
Write-Host "   Repo URL : $repoUrl"   -ForegroundColor White
Write-Host "   Commit   : $commitMsg" -ForegroundColor White
Write-Host ""

$confirm = Read-Host "Lanjutkan upload? (y/n)"
if ($confirm -ne "y" -and $confirm -ne "Y") {
    Write-Host "Upload dibatalkan." -ForegroundColor Red
    Read-Host "Tekan Enter untuk keluar"
    exit
}

# -- Set folder project ke lokasi script --
$projectPath = Split-Path -Parent $MyInvocation.MyCommand.Path
Set-Location $projectPath

Write-Host ""
Write-Host "[INFO] Project folder: $projectPath" -ForegroundColor Cyan

# -- Konfigurasi git global --
Write-Host ""
Write-Host "[INFO] Mengatur konfigurasi Git..." -ForegroundColor Yellow
git config --global user.name  $username
git config --global user.email $email
Write-Host "[OK] Konfigurasi Git selesai" -ForegroundColor Green

# -- Buat .gitignore kalau belum ada --
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

# -- Init Git kalau belum --
Write-Host ""
Write-Host "[INFO] Inisialisasi Git repository..." -ForegroundColor Yellow
if (-not (Test-Path (Join-Path $projectPath ".git"))) {
    git init
    Write-Host "[OK] Git diinisialisasi" -ForegroundColor Green
} else {
    Write-Host "[OK] Git sudah ada sebelumnya" -ForegroundColor Green
}

# -- Set branch ke main --
git branch -M main 2>$null

# -- Add remote --
Write-Host ""
Write-Host "[INFO] Menghubungkan ke GitHub..." -ForegroundColor Yellow
$existingRemote = git remote get-url origin 2>$null
if ($existingRemote) {
    git remote set-url origin $repoUrl
    Write-Host "[OK] Remote URL diperbarui: $repoUrl" -ForegroundColor Green
} else {
    git remote add origin $repoUrl
    Write-Host "[OK] Remote ditambahkan: $repoUrl" -ForegroundColor Green
}

# -- Stage semua file --
Write-Host ""
Write-Host "[INFO] Menambahkan semua file ke staging..." -ForegroundColor Yellow
git add .
Write-Host "[OK] Semua file siap di-commit" -ForegroundColor Green

# -- Commit --
Write-Host ""
Write-Host "[INFO] Commit..." -ForegroundColor Yellow
git commit -m $commitMsg
Write-Host "[OK] Commit berhasil" -ForegroundColor Green

# -- Push --
Write-Host ""
Write-Host "[INFO] Mengupload ke GitHub..." -ForegroundColor Yellow
Write-Host "       (Browser/dialog login GitHub akan muncul, login dengan akun GitHub kamu)" -ForegroundColor Cyan
Write-Host ""
git push -u origin main

if ($LASTEXITCODE -eq 0) {
    Write-Host ""
    Write-Host "=========================================" -ForegroundColor Green
    Write-Host "   [SUKSES] UPLOAD BERHASIL!             " -ForegroundColor Green
    Write-Host "=========================================" -ForegroundColor Green
    Write-Host ""
    Write-Host "   Lihat repository kamu di:" -ForegroundColor White
    Write-Host "   https://github.com/$username/$repoName" -ForegroundColor Cyan
    Write-Host ""
} else {
    Write-Host ""
    Write-Host "=========================================" -ForegroundColor Red
    Write-Host "   [GAGAL] UPLOAD TIDAK BERHASIL         " -ForegroundColor Red
    Write-Host "=========================================" -ForegroundColor Red
    Write-Host ""
    Write-Host "   Kemungkinan penyebab:" -ForegroundColor Yellow
    Write-Host "   1. Repository belum dibuat di GitHub" -ForegroundColor White
    Write-Host "      Buat dulu di: https://github.com/new" -ForegroundColor Cyan
    Write-Host "   2. Username atau nama repo salah" -ForegroundColor White
    Write-Host "   3. Belum login GitHub di PC ini" -ForegroundColor White
    Write-Host ""
}

Read-Host "Tekan Enter untuk menutup"
