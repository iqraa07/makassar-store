# 📖 Panduan Upload Makassar Store ke GitHub

Panduan lengkap dari nol hingga project tampil di GitHub.

---

## ✅ LANGKAH 1 — Install Git (Kalau Belum)

1. Buka browser, pergi ke **https://git-scm.com/download/win**
2. Download installer (pilih versi 64-bit)
3. Jalankan installer, klik **Next** terus sampai selesai (semua default sudah oke)
4. Setelah selesai, buka **PowerShell** dan ketik:
   ```
   git --version
   ```
   Jika muncul `git version 2.x.x` → Git sudah terinstall ✅

---

## ✅ LANGKAH 2 — Buat Akun GitHub (Kalau Belum)

1. Buka **https://github.com**
2. Klik **Sign Up**
3. Isi username, email, password → verifikasi email
4. Login ke GitHub

---

## ✅ LANGKAH 3 — Buat Repository Baru di GitHub

1. Setelah login, klik tombol **+** di pojok kanan atas → pilih **New repository**
2. Isi form:
   - **Repository name**: `makassar-store` *(atau nama yang kamu mau)*
   - **Description**: `Sistem POS Makassar Store berbasis PHP & MySQL`
   - Pilih **Public** *(biar bisa dilihat orang lain)*
   - ❌ **JANGAN centang** "Add a README file" *(karena kita sudah punya)*
   - ❌ **JANGAN centang** "Add .gitignore"
3. Klik **Create repository**
4. Salin URL repository yang muncul, contoh:
   ```
   https://github.com/usernamekamu/makassar-store.git
   ```

---

## ✅ LANGKAH 4 — Jalankan Script Upload Otomatis

1. Buka **File Explorer**
2. Navigasi ke folder:
   ```
   C:\xampp\htdocs\Tugas\kasir\
   ```
3. Klik kanan file **`upload_github.ps1`**
4. Pilih **"Run with PowerShell"**

   > Kalau muncul peringatan keamanan:
   > - Klik **"Open"** atau ketik **R** lalu Enter

5. Script akan meminta kamu mengisi:
   - **GitHub username**: username GitHub kamu (contoh: `iqraa07`)
   - **Nama repository**: nama repo yang dibuat tadi (contoh: `makassar-store`)
   - **Email GitHub**: email yang didaftarkan di GitHub
   - **Pesan commit**: tekan Enter saja untuk pakai default

6. Konfirmasi dengan mengetik **y** lalu Enter

---

## ✅ LANGKAH 5 — Login GitHub saat Push

Saat script menjalankan `git push`, akan muncul **jendela login GitHub**:

- Pilih **"Sign in with your browser"**
- Kamu akan diarahkan ke browser untuk authorize Git
- Klik **Authorize** → selesai, upload akan berjalan otomatis

---

## ✅ LANGKAH 6 — Cek Hasilnya di GitHub

Setelah script selesai dengan pesan **"UPLOAD BERHASIL"**:

1. Buka browser
2. Pergi ke:
   ```
   https://github.com/usernamekamu/makassar-store
   ```
3. Kamu akan melihat semua file project sudah terupload, beserta README dengan foto-foto tampilan aplikasi ✅

---

## 🔄 Upload Lagi / Update (setelah ada perubahan)

Kalau ada perubahan file dan mau update GitHub, jalankan di **PowerShell**:

```powershell
cd "C:\xampp\htdocs\Tugas\kasir"
git add .
git commit -m "Update: deskripsi perubahan yang kamu buat"
git push
```

Atau jalankan script `upload_github.ps1` lagi — script akan otomatis mendeteksi bahwa repo sudah ada.

---

## ❗ Troubleshooting

| Masalah | Solusi |
|---|---|
| `git` tidak dikenal | Install Git dari https://git-scm.com |
| `remote: Repository not found` | Cek nama repo & username, pastikan repo sudah dibuat di GitHub |
| `failed to push — branch protection` | Pastikan branch: jalankan `git branch -M main` dulu |
| Dialog login tidak muncul | Install **GitHub CLI**: https://cli.github.com → jalankan `gh auth login` |
| Script tidak bisa dibuka | Buka PowerShell lalu ketik: `Set-ExecutionPolicy RemoteSigned -Scope CurrentUser` |

---

## 💡 Tips

- **Jangan upload** file sensitif seperti password database ke GitHub publik
- File `config/database.php` sudah masuk `.gitignore` untuk keamanan — sesuaikan jika perlu
- Nama repository di GitHub **tidak bisa** diubah tanpa mengubah URL, jadi pilih nama yang tepat dari awal
