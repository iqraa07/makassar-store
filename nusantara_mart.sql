-- ============================================================
-- NUSANTARA MART - Sistem Kasir Modern
-- Database: nusantara_mart
-- ============================================================

CREATE DATABASE IF NOT EXISTS nusantara_mart CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE nusantara_mart;

-- Tabel Kategori
CREATE TABLE IF NOT EXISTS tbl_kategori (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kode_kategori VARCHAR(10) NOT NULL UNIQUE,
    nama_kategori VARCHAR(100) NOT NULL,
    deskripsi TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Tabel Barang
CREATE TABLE IF NOT EXISTS tbl_barang (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kode_barang VARCHAR(20) NOT NULL UNIQUE,
    nama_barang VARCHAR(200) NOT NULL,
    id_kategori INT NULL,
    harga_beli DECIMAL(15,2) DEFAULT 0,
    harga_jual DECIMAL(15,2) NOT NULL,
    stok INT DEFAULT 0,
    stok_minimum INT DEFAULT 5,
    satuan VARCHAR(20) DEFAULT 'pcs',
    deskripsi TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_kategori) REFERENCES tbl_kategori(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Tabel Member/Pelanggan
CREATE TABLE IF NOT EXISTS tbl_member (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kode_member VARCHAR(20) NOT NULL UNIQUE,
    nama VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    telepon VARCHAR(20),
    alamat TEXT,
    tanggal_lahir DATE NULL,
    jenis_kelamin ENUM('L','P') DEFAULT 'L',
    poin INT DEFAULT 0,
    total_belanja DECIMAL(15,2) DEFAULT 0,
    jumlah_transaksi INT DEFAULT 0,
    status ENUM('aktif','nonaktif') DEFAULT 'aktif',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Tabel Transaksi
CREATE TABLE IF NOT EXISTS tbl_transaksi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kode_transaksi VARCHAR(30) NOT NULL UNIQUE,
    id_member INT NULL,
    total_harga DECIMAL(15,2) NOT NULL,
    diskon DECIMAL(15,2) DEFAULT 0,
    diskon_persen DECIMAL(5,2) DEFAULT 0,
    total_bayar DECIMAL(15,2) NOT NULL,
    uang_bayar DECIMAL(15,2) NOT NULL,
    kembalian DECIMAL(15,2) DEFAULT 0,
    metode_bayar ENUM('tunai','qris','transfer') DEFAULT 'tunai',
    kasir VARCHAR(100) DEFAULT 'Admin',
    catatan TEXT,
    status ENUM('selesai','batal') DEFAULT 'selesai',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_member) REFERENCES tbl_member(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Tabel Detail Transaksi
CREATE TABLE IF NOT EXISTS tbl_detail_transaksi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_transaksi INT NOT NULL,
    id_barang INT NOT NULL,
    nama_barang VARCHAR(200) NOT NULL,
    qty INT NOT NULL,
    harga_satuan DECIMAL(15,2) NOT NULL,
    diskon_item DECIMAL(15,2) DEFAULT 0,
    subtotal DECIMAL(15,2) NOT NULL,
    FOREIGN KEY (id_transaksi) REFERENCES tbl_transaksi(id) ON DELETE CASCADE,
    FOREIGN KEY (id_barang) REFERENCES tbl_barang(id)
) ENGINE=InnoDB;

-- ============================================================
-- DATA SAMPLE
-- ============================================================

-- Kategori
INSERT INTO tbl_kategori (kode_kategori, nama_kategori, deskripsi) VALUES
('KAT001', 'Minuman', 'Air mineral, minuman kemasan, dan minuman segar'),
('KAT002', 'Makanan Ringan', 'Snack, kripik, coklat, dan camilan premium'),
('KAT003', 'Kebutuhan Rumah', 'Produk kebersihan dan peralatan rumah tangga'),
('KAT004', 'Sembako', 'Bahan pokok makanan sehari-hari'),
('KAT005', 'Perawatan Diri', 'Sabun, shampoo, dan produk kecantikan');

-- Barang
INSERT INTO tbl_barang (kode_barang, nama_barang, id_kategori, harga_beli, harga_jual, stok, stok_minimum, satuan) VALUES
('BRG001', 'Aqua 600ml', 1, 2000, 4000, 120, 20, 'botol'),
('BRG002', 'Teh Botol Sosro 450ml', 1, 5000, 8000, 80, 15, 'botol'),
('BRG003', 'Pocari Sweat 500ml', 1, 7000, 11000, 60, 10, 'botol'),
('BRG004', 'Good Day Cappuccino 250ml', 1, 4500, 7500, 100, 20, 'kaleng'),
('BRG005', 'Coca-Cola 390ml', 1, 6000, 10000, 75, 15, 'kaleng'),
('BRG006', 'Indomie Goreng', 4, 2800, 4500, 200, 30, 'pcs'),
('BRG007', 'Mie Sedap Goreng', 4, 2500, 4000, 150, 30, 'pcs'),
('BRG008', 'Beras Premium Pulen 5kg', 4, 55000, 72000, 30, 5, 'karung'),
('BRG009', 'Minyak Goreng Bimoli 1L', 4, 22000, 28000, 40, 10, 'botol'),
('BRG010', 'Gula Pasir 1kg', 4, 13000, 18000, 60, 10, 'kg'),
('BRG011', 'Chitato Original 68g', 2, 11000, 18000, 45, 10, 'pcs'),
('BRG012', 'Pringles Original 107g', 2, 26000, 40000, 35, 8, 'kaleng'),
('BRG013', 'Oreo Original 133g', 2, 10000, 16000, 55, 10, 'pcs'),
('BRG014', 'Tango Wafer Coklat', 2, 5000, 9000, 80, 15, 'pcs'),
('BRG015', 'Sabun Lifebuoy 70g', 5, 5000, 8500, 60, 10, 'pcs'),
('BRG016', 'Shampoo Clear 170ml', 5, 18000, 27000, 30, 8, 'botol'),
('BRG017', 'Pasta Gigi Pepsodent 75ml', 5, 8000, 13000, 50, 10, 'pcs'),
('BRG018', 'Detergen Rinso 800g', 3, 18000, 27000, 40, 8, 'pcs'),
('BRG019', 'Sabun Cuci Piring Sunlight 755ml', 3, 16000, 23000, 35, 8, 'botol'),
('BRG020', 'Tisu Paseo 250 sheet', 3, 12000, 19000, 45, 10, 'pack');

-- Member
INSERT INTO tbl_member (kode_member, nama, email, telepon, alamat, poin, total_belanja, jumlah_transaksi) VALUES
('MBR001', 'Budi Santoso', 'budi.santoso@gmail.com', '081234567890', 'Jl. Merdeka No. 10 Rt 03/05, Jakarta Pusat 10110', 250, 500000, 8),
('MBR002', 'Siti Rahayu', 'siti.rahayu@yahoo.com', '082345678901', 'Jl. Pemuda No. 5A, Bandung 40123', 180, 360000, 6),
('MBR003', 'Ahmad Fauzi', 'ahmad.fauzi@email.com', '083456789012', 'Jl. Sudirman No. 88, Surabaya 60271', 420, 840000, 14),
('MBR004', 'Dewi Lestari', 'dewi.lestari@gmail.com', '084567890123', 'Jl. Pahlawan No. 21, Yogyakarta 55224', 95, 190000, 3),
('MBR005', 'Rizky Pratama', 'rizky.p@gmail.com', '085678901234', 'Jl. Veteran No. 7, Malang 65112', 310, 620000, 10);

-- Transaksi Sample (2 hari terakhir)
INSERT INTO tbl_transaksi (kode_transaksi, id_member, total_harga, diskon, total_bayar, uang_bayar, kembalian, metode_bayar, kasir, created_at) VALUES
('TRX20260419001', 1, 45500, 0, 45500, 50000, 4500, 'tunai', 'Admin', NOW() - INTERVAL 2 HOUR),
('TRX20260419002', NULL, 28000, 0, 28000, 30000, 2000, 'tunai', 'Admin', NOW() - INTERVAL 3 HOUR),
('TRX20260419003', 2, 72000, 5000, 67000, 70000, 3000, 'tunai', 'Admin', NOW() - INTERVAL 4 HOUR),
('TRX20260418001', 3, 120500, 10000, 110500, 120000, 9500, 'tunai', 'Admin', NOW() - INTERVAL 1 DAY),
('TRX20260418002', NULL, 54000, 0, 54000, 60000, 6000, 'qris', 'Admin', NOW() - INTERVAL 1 DAY - INTERVAL 2 HOUR);

INSERT INTO tbl_detail_transaksi (id_transaksi, id_barang, nama_barang, qty, harga_satuan, subtotal) VALUES
(1, 1, 'Aqua 600ml', 3, 4000, 12000),
(1, 6, 'Indomie Goreng', 5, 4500, 22500),
(1, 13, 'Oreo Original 133g', 1, 16000, 16000),
(2, 2, 'Teh Botol Sosro 450ml', 2, 8000, 16000),
(2, 14, 'Tango Wafer Coklat', 1, 9000, 9000),
(2, 15, 'Sabun Lifebuoy 70g', 1, 8500, 8500),
(3, 8, 'Beras Premium Pulen 5kg', 1, 72000, 72000),
(4, 9, 'Minyak Goreng Bimoli 1L', 2, 28000, 56000),
(4, 10, 'Gula Pasir 1kg', 2, 18000, 36000),
(4, 6, 'Indomie Goreng', 6, 4500, 27000),
(5, 3, 'Pocari Sweat 500ml', 2, 11000, 22000),
(5, 11, 'Chitato Original 68g', 1, 18000, 18000),
(5, 7, 'Mie Sedap Goreng', 3, 4000, 12000);
