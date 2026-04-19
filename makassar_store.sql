-- ============================================================
-- MAKASSAR STORE — Sistem Kasir Modern
-- Database  : makassar_store
-- Versi     : 3.0
-- Khas Makassar 🏬
-- ============================================================
-- Cara import:
--   1. Buka phpMyAdmin → tab "Import"
--   2. Pilih file ini → klik "Kirim/Go"
--   ATAU via CMD: mysql -u root < makassar_store.sql
-- ============================================================

SET SQL_MODE   = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone  = "+08:00";
SET NAMES      utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ─── Buat & Gunakan Database ───────────────────────────────
CREATE DATABASE IF NOT EXISTS `makassar_store`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE `makassar_store`;

-- ============================================================
-- STRUKTUR TABEL
-- ============================================================

-- ─── tbl_users ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `tbl_users` (
    `id`         INT          NOT NULL AUTO_INCREMENT,
    `nama`       VARCHAR(100) NOT NULL,
    `username`   VARCHAR(50)  NOT NULL,
    `email`      VARCHAR(100) DEFAULT NULL,
    `password`   VARCHAR(255) NOT NULL,
    `role`       ENUM('admin','kasir') NOT NULL DEFAULT 'kasir',
    `status`     ENUM('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
    `last_login` DATETIME     DEFAULT NULL,
    `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── tbl_kategori ──────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `tbl_kategori` (
    `id`            INT          NOT NULL AUTO_INCREMENT,
    `kode_kategori` VARCHAR(10)  NOT NULL,
    `nama_kategori` VARCHAR(100) NOT NULL,
    `deskripsi`     TEXT,
    `created_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_kode_kategori` (`kode_kategori`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── tbl_barang ────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `tbl_barang` (
    `id`           INT           NOT NULL AUTO_INCREMENT,
    `kode_barang`  VARCHAR(20)   NOT NULL,
    `nama_barang`  VARCHAR(200)  NOT NULL,
    `id_kategori`  INT           DEFAULT NULL,
    `harga_beli`   DECIMAL(15,2) NOT NULL DEFAULT '0.00',
    `harga_jual`   DECIMAL(15,2) NOT NULL,
    `stok`         INT           NOT NULL DEFAULT '0',
    `stok_minimum` INT           NOT NULL DEFAULT '5',
    `satuan`       VARCHAR(20)   NOT NULL DEFAULT 'pcs',
    `deskripsi`    TEXT,
    `created_at`   TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`   TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_kode_barang` (`kode_barang`),
    KEY `fk_barang_kategori` (`id_kategori`),
    CONSTRAINT `fk_barang_kategori`
        FOREIGN KEY (`id_kategori`) REFERENCES `tbl_kategori` (`id`)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── tbl_member ────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `tbl_member` (
    `id`               INT           NOT NULL AUTO_INCREMENT,
    `kode_member`      VARCHAR(20)   NOT NULL,
    `nama`             VARCHAR(100)  NOT NULL,
    `email`            VARCHAR(100)  DEFAULT NULL,
    `telepon`          VARCHAR(20)   DEFAULT NULL,
    `alamat`           TEXT,
    `tanggal_lahir`    DATE          DEFAULT NULL,
    `jenis_kelamin`    ENUM('L','P') NOT NULL DEFAULT 'L',
    `poin`             INT           NOT NULL DEFAULT '0',
    `total_belanja`    DECIMAL(15,2) NOT NULL DEFAULT '0.00',
    `jumlah_transaksi` INT           NOT NULL DEFAULT '0',
    `status`           ENUM('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
    `created_at`       TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_kode_member` (`kode_member`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── tbl_transaksi ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `tbl_transaksi` (
    `id`             INT           NOT NULL AUTO_INCREMENT,
    `kode_transaksi` VARCHAR(30)   NOT NULL,
    `id_member`      INT           DEFAULT NULL,
    `total_harga`    DECIMAL(15,2) NOT NULL,
    `diskon`         DECIMAL(15,2) NOT NULL DEFAULT '0.00',
    `diskon_persen`  DECIMAL(5,2)  NOT NULL DEFAULT '0.00',
    `total_bayar`    DECIMAL(15,2) NOT NULL,
    `uang_bayar`     DECIMAL(15,2) NOT NULL,
    `kembalian`      DECIMAL(15,2) NOT NULL DEFAULT '0.00',
    `metode_bayar`   ENUM('tunai','qris','transfer') NOT NULL DEFAULT 'tunai',
    `kasir`          VARCHAR(100)  NOT NULL DEFAULT 'Admin',
    `catatan`        TEXT,
    `status`         ENUM('selesai','batal') NOT NULL DEFAULT 'selesai',
    `created_at`     TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_kode_transaksi` (`kode_transaksi`),
    KEY `fk_transaksi_member` (`id_member`),
    CONSTRAINT `fk_transaksi_member`
        FOREIGN KEY (`id_member`) REFERENCES `tbl_member` (`id`)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── tbl_detail_transaksi ──────────────────────────────────
CREATE TABLE IF NOT EXISTS `tbl_detail_transaksi` (
    `id`           INT           NOT NULL AUTO_INCREMENT,
    `id_transaksi` INT           NOT NULL,
    `id_barang`    INT           NOT NULL,
    `nama_barang`  VARCHAR(200)  NOT NULL,
    `qty`          INT           NOT NULL,
    `harga_satuan` DECIMAL(15,2) NOT NULL,
    `diskon_item`  DECIMAL(15,2) NOT NULL DEFAULT '0.00',
    `subtotal`     DECIMAL(15,2) NOT NULL,
    PRIMARY KEY (`id`),
    KEY `fk_detail_transaksi` (`id_transaksi`),
    KEY `fk_detail_barang` (`id_barang`),
    CONSTRAINT `fk_detail_transaksi`
        FOREIGN KEY (`id_transaksi`) REFERENCES `tbl_transaksi` (`id`)
        ON DELETE CASCADE,
    CONSTRAINT `fk_detail_barang`
        FOREIGN KEY (`id_barang`) REFERENCES `tbl_barang` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- DATA AWAL (SEED)
-- ============================================================

-- ─── Admin Default ─────────────────────────────────────────
-- Password: admin123
INSERT INTO `tbl_users` (`nama`, `username`, `email`, `password`, `role`) VALUES
('Administrator', 'admin', 'admin@makassarstore.id',
 '$2y$10$emeVz1kEaPKdW9gKP1lMV.e1C8k9INWGOHhK13Rp9htPi4fW3Gk.6', 'admin');
-- Username : admin
-- Password : admin123

-- ─── Kategori ──────────────────────────────────────────────
INSERT INTO `tbl_kategori` (`kode_kategori`, `nama_kategori`, `deskripsi`) VALUES
('KAT001', 'Minuman',         'Air mineral, minuman kemasan, dan minuman segar'),
('KAT002', 'Makanan Ringan',  'Snack, kripik, coklat, dan camilan premium'),
('KAT003', 'Kebutuhan Rumah', 'Produk kebersihan dan peralatan rumah tangga'),
('KAT004', 'Sembako',         'Bahan pokok makanan sehari-hari'),
('KAT005', 'Perawatan Diri',  'Sabun, shampoo, dan produk kecantikan');

-- ─── Barang ────────────────────────────────────────────────
INSERT INTO `tbl_barang`
    (`kode_barang`, `nama_barang`, `id_kategori`,
     `harga_beli`, `harga_jual`, `stok`, `stok_minimum`, `satuan`)
VALUES
-- Minuman (id_kategori=1)
('BRG001', 'Aqua 600ml',                    1,  2000,  4000, 120, 20, 'botol'),
('BRG002', 'Teh Botol Sosro 450ml',         1,  5000,  8000,  80, 15, 'botol'),
('BRG003', 'Pocari Sweat 500ml',            1,  7000, 11000,  60, 10, 'botol'),
('BRG004', 'Good Day Cappuccino 250ml',     1,  4500,  7500, 100, 20, 'kaleng'),
('BRG005', 'Coca-Cola 390ml',               1,  6000, 10000,  75, 15, 'kaleng'),
-- Sembako (id_kategori=4)
('BRG006', 'Indomie Goreng',                4,  2800,  4500, 200, 30, 'pcs'),
('BRG007', 'Mie Sedap Goreng',              4,  2500,  4000, 150, 30, 'pcs'),
('BRG008', 'Beras Premium Pulen 5kg',       4, 55000, 72000,  30,  5, 'karung'),
('BRG009', 'Minyak Goreng Bimoli 1L',       4, 22000, 28000,  40, 10, 'botol'),
('BRG010', 'Gula Pasir 1kg',                4, 13000, 18000,  60, 10, 'kg'),
-- Makanan Ringan (id_kategori=2)
('BRG011', 'Chitato Original 68g',          2, 11000, 18000,  45, 10, 'pcs'),
('BRG012', 'Pringles Original 107g',        2, 26000, 40000,  35,  8, 'kaleng'),
('BRG013', 'Oreo Original 133g',            2, 10000, 16000,  55, 10, 'pcs'),
('BRG014', 'Tango Wafer Coklat',            2,  5000,  9000,  80, 15, 'pcs'),
-- Perawatan Diri (id_kategori=5)
('BRG015', 'Sabun Lifebuoy 70g',            5,  5000,  8500,  60, 10, 'pcs'),
('BRG016', 'Shampoo Clear 170ml',           5, 18000, 27000,  30,  8, 'botol'),
('BRG017', 'Pasta Gigi Pepsodent 75ml',     5,  8000, 13000,  50, 10, 'pcs'),
-- Kebutuhan Rumah (id_kategori=3)
('BRG018', 'Detergen Rinso 800g',           3, 18000, 27000,  40,  8, 'pcs'),
('BRG019', 'Sabun Cuci Piring Sunlight 755ml', 3, 16000, 23000, 35, 8, 'botol'),
('BRG020', 'Tisu Paseo 250 sheet',          3, 12000, 19000,  45, 10, 'pack');

-- ─── Member ────────────────────────────────────────────────
INSERT INTO `tbl_member`
    (`kode_member`, `nama`, `email`, `telepon`, `alamat`,
     `poin`, `total_belanja`, `jumlah_transaksi`)
VALUES
('MBR001', 'Budi Santoso',  'budi.santoso@gmail.com',  '081234567890',
    'Jl. Penghibur No. 10, Makassar 90111',  250,  500000,  8),
('MBR002', 'Siti Rahayu',   'siti.rahayu@yahoo.com',   '082345678901',
    'Jl. Somba Opu No. 5A, Makassar 90111',  180,  360000,  6),
('MBR003', 'Ahmad Fauzi',   'ahmad.fauzi@email.com',   '083456789012',
    'Jl. Pettarani No. 88, Makassar 90222',  420,  840000, 14),
('MBR004', 'Dewi Lestari',  'dewi.lestari@gmail.com',  '084567890123',
    'Jl. Rappocini No. 21, Makassar 90222',   95,  190000,  3),
('MBR005', 'Rizky Pratama', 'rizky.p@gmail.com',        '085678901234',
    'Jl. Urip Sumoharjo No. 7, Makassar 90232', 310, 620000, 10);

-- ─── Transaksi Sample ──────────────────────────────────────
INSERT INTO `tbl_transaksi`
    (`kode_transaksi`, `id_member`, `total_harga`, `diskon`,
     `total_bayar`, `uang_bayar`, `kembalian`,
     `metode_bayar`, `kasir`, `created_at`)
VALUES
('TRX20260419001',    1,  45500,     0,  45500,  50000,  4500, 'tunai',    'Admin', NOW() - INTERVAL  2 HOUR),
('TRX20260419002', NULL,  28000,     0,  28000,  30000,  2000, 'tunai',    'Admin', NOW() - INTERVAL  3 HOUR),
('TRX20260419003',    2,  72000,  5000,  67000,  70000,  3000, 'tunai',    'Admin', NOW() - INTERVAL  4 HOUR),
('TRX20260418001',    3, 120500, 10000, 110500, 120000,  9500, 'tunai',    'Admin', NOW() - INTERVAL  1 DAY),
('TRX20260418002', NULL,  54000,     0,  54000,  60000,  6000, 'qris',     'Admin', NOW() - INTERVAL  1 DAY - INTERVAL 2 HOUR);

-- ─── Detail Transaksi ──────────────────────────────────────
INSERT INTO `tbl_detail_transaksi`
    (`id_transaksi`, `id_barang`, `nama_barang`, `qty`, `harga_satuan`, `subtotal`)
VALUES
-- TRX20260419001
(1,  1, 'Aqua 600ml',               3,  4000, 12000),
(1,  6, 'Indomie Goreng',           5,  4500, 22500),
(1, 13, 'Oreo Original 133g',       1, 16000, 16000),
-- TRX20260419002
(2,  2, 'Teh Botol Sosro 450ml',    2,  8000, 16000),
(2, 14, 'Tango Wafer Coklat',       1,  9000,  9000),
(2, 15, 'Sabun Lifebuoy 70g',       1,  8500,  8500),
-- TRX20260419003
(3,  8, 'Beras Premium Pulen 5kg',  1, 72000, 72000),
-- TRX20260418001
(4,  9, 'Minyak Goreng Bimoli 1L',  2, 28000, 56000),
(4, 10, 'Gula Pasir 1kg',           2, 18000, 36000),
(4,  6, 'Indomie Goreng',           6,  4500, 27000),
-- TRX20260418002
(5,  3, 'Pocari Sweat 500ml',       2, 11000, 22000),
(5, 11, 'Chitato Original 68g',     1, 18000, 18000),
(5,  7, 'Mie Sedap Goreng',         3,  4000, 12000);

-- ─── Selesai ───────────────────────────────────────────────
SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- LOGIN DEFAULT SETELAH IMPORT:
--   Username : admin
--   Password : password
-- (Ganti password di menu profil setelah login pertama)
-- ============================================================
