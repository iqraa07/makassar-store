<div align="center">

# 🏬 Makassar Store — Point of Sale System

**Sistem kasir modern berbasis web untuk toko ritel khas Makassar.**  
Dibangun dengan PHP Native + MySQL, desain dark-mode premium, dan fitur lengkap siap pakai.

[![PHP](https://img.shields.io/badge/PHP-8.x-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://php.net)
[![MySQL](https://img.shields.io/badge/MySQL-8.x-4479A1?style=for-the-badge&logo=mysql&logoColor=white)](https://mysql.com)
[![XAMPP](https://img.shields.io/badge/XAMPP-Compatible-FB7A24?style=for-the-badge&logo=xampp&logoColor=white)](https://apachefriends.org)
[![License](https://img.shields.io/badge/License-MIT-10B981?style=for-the-badge)](LICENSE)

</div>

---

## 📋 Daftar Isi

- [Tentang Proyek](#-tentang-proyek)
- [Fitur Utama](#-fitur-utama)
- [Tampilan Aplikasi](#-tampilan-aplikasi)
- [Teknologi yang Digunakan](#-teknologi-yang-digunakan)
- [Instalasi](#-instalasi)
- [Konfigurasi Database](#-konfigurasi-database)
- [Akun Default](#-akun-default)
- [Struktur Folder](#-struktur-folder)
- [Lisensi](#-lisensi)

---

## 📌 Tentang Proyek

**Makassar Store POS** adalah aplikasi kasir berbasis web yang dirancang untuk membantu pengelolaan toko ritel secara digital. Sistem ini mencakup seluruh alur operasional toko — mulai dari manajemen produk, proses transaksi penjualan, pencatatan member, hingga laporan keuangan dengan ekspor Excel.

> *"Belanja Mudah, Hidup Berkah — Khas Makassar"*

---

## ✨ Fitur Utama

| Modul | Deskripsi |
|---|---|
| 🔐 **Autentikasi** | Login & Register dengan kode registrasi khusus, multi-role (Admin / Kasir) |
| 📊 **Dashboard** | Statistik real-time: omset harian/bulanan, produk terlaris, transaksi terbaru, stok kritis |
| 🛒 **Transaksi POS** | Interface kasir intuitif — klik produk, atur qty, pilih metode bayar, cetak struk |
| 📦 **Stok Barang** | CRUD produk lengkap dengan kode barang, harga beli/jual, stok, satuan, dan status |
| 🏷️ **Kategori** | Pengelompokan produk dengan icon emoji dan tracking total stok per kategori |
| 👥 **Member** | Manajemen pelanggan dengan sistem poin, riwayat belanja, dan statistik top member |
| 📈 **Laporan Penjualan** | Filter periode/metode/member, grafik interaktif, rekap per metode, ekspor ke Excel |
| 👤 **Profil & Setelan** | Setiap user bisa edit nama, username, email, dan ganti password secara mandiri |

---

## 📸 Tampilan Aplikasi

### 1. Halaman Login
Tampilan awal sistem dengan form login yang bersih, background animasi bintang, dan identitas brand Makassar Store.

![Login](documentasi/1_login.png)

---

### 2. Halaman Register
Form pendaftaran akun baru dengan validasi kode registrasi dari Admin — menjaga keamanan sistem dari akses tidak sah.

![Register](documentasi/2_register.png)

---

### 3. Dashboard — Ringkasan Harian
Tampilan utama setelah login. Menampilkan statistik real-time: omset hari ini & bulan ini, total produk, jumlah member aktif, grafik penjualan 7 hari, dan produk terlaris.

![Dashboard](documentasi/3_dashboard.png)

---

### 4. Dashboard — Transaksi Terbaru & Stok Kritis
Bagian bawah dashboard menampilkan riwayat transaksi lengkap dengan metode bayar, total, dan peringatan stok kritis otomatis.

![Dashboard Transaksi](documentasi/4_dashboard_transaksi.png)

---

### 5. Transaksi Kasir — POS Interface
Halaman utama kasir dengan grid produk, filter kategori, pencarian real-time (support barcode scan), dan panel keranjang belanja di sisi kanan.

![Transaksi POS](documentasi/5_transaksi_pos.png)

---

### 6. Modal Proses Pembayaran
Dialog pembayaran dengan pilihan metode (Tunai / QRIS / Transfer), input uang bayar, tombol nominal cepat, dan kalkulasi kembalian otomatis.

![Proses Pembayaran](documentasi/6_proses_pembayaran.png)

---

### 7. Struk Pembayaran (Preview)
Preview struk digital setelah transaksi berhasil, dengan opsi cetak struk fisik atau mulai transaksi baru.

![Struk Preview](documentasi/7_struk_preview.png)

---

### 8. Cetak Struk — Halaman Print
Tampilan struk format thermal printer dengan kode transaksi, detail item, total bayar, kembalian, dan kode transaksi unik.

![Cetak Struk](documentasi/8_cetak_struk.png)

---

### 9. Manajemen Stok Barang
Tabel inventori lengkap dengan kode barang, kategori, harga beli & jual, stok dengan visual bar, satuan, status, dan tombol aksi (tambah stok, edit, hapus).

![Stok Barang](documentasi/9_stok_barang.png)

---

### 10. Manajemen Kategori
Daftar kategori produk dengan icon, deskripsi, jumlah produk, total stok, dan tanggal dibuat.

![Kategori](documentasi/10_kategori.png)

---

### 11. Manajemen Member
Data pelanggan terregistrasi: poin loyalitas, total belanja, jumlah transaksi, status aktif/nonaktif, dan statistik top member.

![Member](documentasi/11_member.png)

---

### 12. Laporan Penjualan — Statistik & Grafik
Filter laporan by rentang tanggal, metode bayar, dan member. Menampilkan total omset, transaksi, rata-rata, diskon, grafik periode, dan rekap per metode pembayaran.

![Laporan Statistik](documentasi/12_laporan_statistik.png)

---

### 13. Laporan Penjualan — Detail Transaksi
Tabel detail setiap transaksi lengkap dengan kode, member, jumlah item, subtotal, diskon, total, metode bayar, dan tanggal.

![Laporan Detail](documentasi/13_laporan_detail.png)

---

### 14. Profil Saya — Edit Profil
Halaman pengaturan akun: ubah nama lengkap, username, dan email. Tersedia untuk semua role (Admin maupun Kasir). Role hanya bisa diubah oleh Administrator.

![Edit Profil](documentasi/14_profil_edit.png)

---

### 15. Profil Saya — Ganti Password
Form ganti password dengan validasi password lama, strength indicator real-time, konfirmasi password, dan tips keamanan.

![Ganti Password](documentasi/15_profil_password.png)

---

### 16. Export Excel — Laporan Penjualan
Hasil ekspor laporan ke format `.xls` dengan judul, periode, ringkasan statistik, dan tabel data yang rapi — siap digunakan untuk keperluan administrasi.

![Export Excel](documentasi/16_export_excel.png)

---

## 🛠️ Teknologi yang Digunakan

| Layer | Teknologi |
|---|---|
| **Backend** | PHP 8.x (Native, tanpa framework) |
| **Database** | MySQL 8.x via `mysqli` |
| **Frontend** | HTML5, CSS3 (Vanilla), JavaScript (ES6+) |
| **Chart** | Chart.js (via CDN) |
| **Icons** | Font Awesome 6.5 |
| **Font** | Plus Jakarta Sans (Google Fonts) |
| **Server** | XAMPP (Apache + MySQL) |

---

## 🚀 Instalasi

### Prasyarat
- XAMPP (versi 8.x atau lebih baru)
- PHP 8.0+
- MySQL 8.0+
- Browser modern (Chrome, Firefox, Edge)

### Langkah Instalasi

**1. Clone atau download repository ini:**
```bash
git clone https://github.com/username/makassar-store.git
```

**2. Pindahkan folder ke direktori XAMPP:**
```
C:\xampp\htdocs\kasir\
```

**3. Jalankan XAMPP:**
- Aktifkan **Apache** dan **MySQL** dari XAMPP Control Panel.

**4. Import database:**
- Buka `http://localhost/phpmyadmin`
- Buat database baru bernama `makassar_store`
- Import file `makassar_store.sql` yang ada di root folder proyek

**5. Sesuaikan konfigurasi database** *(jika diperlukan)*:

Edit file `config/database.php`:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');    // sesuaikan username MySQL
define('DB_PASS', '');        // sesuaikan password MySQL
define('DB_NAME', 'makassar_store');
```

**6. Buka aplikasi di browser:**
```
http://localhost/kasir/
```

---

## 🗄️ Konfigurasi Database

File konfigurasi utama berada di `config/database.php`. Tabel-tabel yang digunakan:

| Tabel | Fungsi |
|---|---|
| `tbl_users` | Data akun pengguna (Admin & Kasir) |
| `tbl_kategori` | Kategori produk |
| `tbl_barang` | Data produk & inventori |
| `tbl_member` | Data pelanggan member |
| `tbl_transaksi` | Header transaksi penjualan |
| `tbl_detail_transaksi` | Item detail per transaksi |

---

## 🔑 Akun Default

Setelah instalasi, akun berikut tersedia secara otomatis:

| Role | Username | Password |
|---|---|---|
| **Admin** | `admin` | `admin123` |

> ⚠️ **Penting:** Segera ganti password default setelah pertama kali login melalui menu **Profil & Setelan → Ganti Password**.

Untuk mendaftarkan kasir baru, gunakan kode registrasi:
```
MKSTR2026
```
*(Kode ini bisa diubah di `config/database.php` → konstanta `REGISTER_CODE`)*

---

## 📁 Struktur Folder

```
kasir/
├── assets/
│   └── css/
│       └── style.css              # Stylesheet utama (dark-mode premium)
├── config/
│   └── database.php               # Konfigurasi DB & fungsi utilitas
├── documentasi/                   # Screenshot tampilan aplikasi
│   ├── 1_login.png
│   ├── 2_register.png
│   ├── 3_dashboard.png
│   ├── 4_dashboard_transaksi.png
│   ├── 5_transaksi_pos.png
│   ├── 6_proses_pembayaran.png
│   ├── 7_struk_preview.png
│   ├── 8_cetak_struk.png
│   ├── 9_stok_barang.png
│   ├── 10_kategori.png
│   ├── 11_member.png
│   ├── 12_laporan_statistik.png
│   ├── 13_laporan_detail.png
│   ├── 14_profil_edit.png
│   ├── 15_profil_password.png
│   └── 16_export_excel.png
├── includes/
│   ├── auth.php                   # Middleware autentikasi & session
│   ├── header.php                 # Layout sidebar + topbar
│   └── footer.php                 # Script global & penutup HTML
├── dashboard.php                  # Halaman dashboard utama
├── transaksi.php                  # Interface kasir POS
├── barang.php                     # Manajemen stok produk
├── kategori.php                   # Manajemen kategori
├── member.php                     # Manajemen member
├── laporan.php                    # Laporan & export Excel
├── profil.php                     # Pengaturan profil user
├── struk.php                      # Cetak struk transaksi
├── login.php                      # Halaman login & register
├── logout.php                     # Proses logout
├── makassar_store.sql             # File dump database
└── README.md                      # Dokumentasi proyek
```

---

## 📄 Lisensi

Proyek ini dibuat untuk keperluan **tugas / pembelajaran** dan didistribusikan di bawah lisensi [MIT](LICENSE).

---

<div align="center">

Dibuat dengan ❤️ untuk Makassar  
**Makassar Store POS v3.0** · 2026

</div>
