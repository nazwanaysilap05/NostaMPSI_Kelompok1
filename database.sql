-- Sistem Informasi Stok Barang - Nosta Coffee Heritage House
-- CATATAN IMPOR ULANG: jika database stok_nosta sudah ada dan ingin memakai data barang baru,
-- hapus dulu database tersebut di phpMyAdmin (tab Operations > Delete the database), lalu impor file ini.
-- (Menghapus database juga menghapus semua transaksi dan akun pengguna lama.)
CREATE DATABASE IF NOT EXISTS stok_nosta CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE stok_nosta;

CREATE TABLE IF NOT EXISTS pengguna (
  id_pengguna INT AUTO_INCREMENT PRIMARY KEY, nama VARCHAR(100) NOT NULL,
  username VARCHAR(50) NOT NULL UNIQUE, password VARCHAR(255) NOT NULL,
  peran ENUM('admin','karyawan') NOT NULL DEFAULT 'karyawan') ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS kategori (
  id_kategori INT AUTO_INCREMENT PRIMARY KEY, nama_kategori VARCHAR(60) NOT NULL) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS pemasok (
  id_pemasok INT AUTO_INCREMENT PRIMARY KEY, nama_pemasok VARCHAR(100) NOT NULL, kontak VARCHAR(50)) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS barang (
  kode_barang VARCHAR(20) PRIMARY KEY, nama_barang VARCHAR(100) NOT NULL, id_kategori INT NOT NULL,
  satuan VARCHAR(20) NOT NULL, stok DECIMAL(10,2) NOT NULL DEFAULT 0, stok_minimum DECIMAL(10,2) NOT NULL DEFAULT 0,
  FOREIGN KEY (id_kategori) REFERENCES kategori(id_kategori)) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS barang_masuk (
  id_masuk INT AUTO_INCREMENT PRIMARY KEY, kode_barang VARCHAR(20) NOT NULL, id_pemasok INT NULL,
  id_pengguna INT NOT NULL, tanggal DATE NOT NULL, jumlah DECIMAL(10,2) NOT NULL,
  harga_beli DECIMAL(12,2) NOT NULL DEFAULT 0, tgl_kedaluwarsa DATE NULL,
  nama_barista VARCHAR(100) NULL,
  FOREIGN KEY (kode_barang) REFERENCES barang(kode_barang),
  FOREIGN KEY (id_pemasok) REFERENCES pemasok(id_pemasok),
  FOREIGN KEY (id_pengguna) REFERENCES pengguna(id_pengguna)) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS barang_keluar (
  id_keluar INT AUTO_INCREMENT PRIMARY KEY, kode_barang VARCHAR(20) NOT NULL, id_pengguna INT NOT NULL,
  tanggal DATE NOT NULL, jumlah DECIMAL(10,2) NOT NULL,
  keterangan ENUM('Pemakaian','Rusak','Kedaluwarsa') NOT NULL DEFAULT 'Pemakaian',
  nama_barista VARCHAR(100) NULL,
  FOREIGN KEY (kode_barang) REFERENCES barang(kode_barang),
  FOREIGN KEY (id_pengguna) REFERENCES pengguna(id_pengguna)) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS stok_opname (
  id_opname INT AUTO_INCREMENT PRIMARY KEY, kode_barang VARCHAR(20) NOT NULL, id_pengguna INT NOT NULL,
  tanggal DATE NOT NULL, stok_sistem DECIMAL(10,2) NOT NULL, stok_fisik DECIMAL(10,2) NOT NULL,
  selisih DECIMAL(10,2) NOT NULL, status_persetujuan ENUM('sesuai','menunggu','disetujui','ditolak') NOT NULL,
  FOREIGN KEY (kode_barang) REFERENCES barang(kode_barang),
  FOREIGN KEY (id_pengguna) REFERENCES pengguna(id_pengguna)) ENGINE=InnoDB;
-- ============================================================
-- DATA AWAL - disalin dari lembar stok kafe (foto), Jumat 13 November
-- ============================================================
INSERT INTO kategori (id_kategori, nama_kategori) VALUES
 (1,'Kopi dan Susu'),
 (2,'Sirup dan Saus'),
 (3,'Bubuk dan Teh'),
 (4,'Buah dan Pelengkap'),
 (5,'Perlengkapan'),
 (6,'Air, Es, dan Soda');

-- Contoh pemasok (ganti dengan pemasok asli lewat menu Pemasok)
INSERT INTO pemasok (nama_pemasok, kontak) VALUES ('Pemasok Contoh 1','-'),('Pemasok Contoh 2','-');

-- PERHATIAN: kolom SATUAN dan STOK MINIMUM TIDAK ada di foto. Nilainya PERKIRAAN saja
-- (ditandai pada tiap baris) dan perlu dikoreksi lewat menu Data Barang.
-- Stok awal = angka kolom STOCK di foto ('-' = 0). Baris bertanda '?? KONFIRMASI' perlu dicek ulang.
INSERT INTO barang (kode_barang, nama_barang, id_kategori, satuan, stok, stok_minimum) VALUES
 ('BRG-001','Kopi Arabica (Papaparas)',1,'kg',28,5),  -- stok: 28 (bisa juga terbaca 78); satuan & min = perkiraan
 ('BRG-002','Whip Cream (Gold Label)',1,'pak',4,2),  -- satuan & min = perkiraan
 ('BRG-003','Fresh Milk (Greenfield)',1,'liter',18,10),  -- tertulis F.MILK; catatan tangan soal Indomilk tidak terbaca jelas; satuan & min = perkiraan
 ('BRG-004','Butterscotch Syrup (Dripp)',2,'botol',5,2),  -- satuan & min = perkiraan
 ('BRG-005','Pandan Syrup (Dripp)',2,'botol',1,2),  -- satuan & min = perkiraan
 ('BRG-006','Hazelnut Syrup (Dripp)',2,'botol',0,2),  -- satuan & min = perkiraan
 ('BRG-007','Tiramisu (Dripp)',2,'botol',1,2),  -- satuan & min = perkiraan
 ('BRG-008','Pistachio (Dripp)',2,'botol',3,2),  -- satuan & min = perkiraan
 ('BRG-009','Air (Cleo)',6,'galon',5,2),  -- satuan & min = perkiraan
 ('BRG-010','Ice Cube (Antar)',6,'pak',4,3),  -- satuan & min = perkiraan
 ('BRG-011','Cup + Lid (Cyras)',5,'pak',16,5),  -- satuan & min = perkiraan
 ('BRG-012','Giffard Mint',2,'botol',1,1),  -- satuan & min = perkiraan
 ('BRG-013','Straw (Pajak)',5,'pak',1,1),  -- satuan & min = perkiraan
 ('BRG-014','Brown Sugar (Toffin)',2,'botol',1,1),  -- satuan & min = perkiraan
 ('BRG-015','Caramel (Monin)',2,'botol',2,1),  -- satuan & min = perkiraan
 ('BRG-016','Pure Matcha (Cy Matcha)',3,'bungkus',1,1),  -- satuan & min = perkiraan
 ('BRG-017','Simple Syrup (Gula)',2,'botol',2,2),  -- satuan & min = perkiraan
 ('BRG-018','Strawberry Fresh (Buah)',4,'kg',2,1),  -- satuan & min = perkiraan
 ('BRG-019','Casei (Yakult)',1,'botol',4,3),  -- satuan & min = perkiraan
 ('BRG-020','Strawberry Syrup (Monin)',2,'botol',2,1),  -- satuan & min = perkiraan
 ('BRG-021','Mint Leaves (Buah)',4,'ikat',0,1),  -- satuan & min = perkiraan
 ('BRG-022','Peach Syrup (Dripp)',2,'botol',1,1),  -- satuan & min = perkiraan
 ('BRG-023','Peach Kaleng (Naraya)',4,'kaleng',9,3),  -- ?? KONFIRMASI: ada angka dicoret sebelum angka 9; satuan & min = perkiraan
 ('BRG-024','Lemon Fresh (Buah)',4,'kg',5,2),  -- ?? KONFIRMASI: ada angka dicoret sebelum angka 5; satuan & min = perkiraan
 ('BRG-025','Cranberry Juice (Diamond)',4,'botol',10,3),  -- stok: 10 (bisa juga terbaca 16); satuan & min = perkiraan
 ('BRG-026','Choco Powder (Korte)',3,'pak',5,2),  -- satuan & min = perkiraan
 ('BRG-027','Corn Starch (Maizena)',3,'pak',0,1),  -- satuan & min = perkiraan
 ('BRG-028','Nata de Coco (Kara)',4,'pak',2,1),  -- satuan & min = perkiraan
 ('BRG-029','Salted Caramel Sauce (Catcher)',2,'botol',5,1),  -- satuan & min = perkiraan
 ('BRG-030','Himalayan Salt (Pink)',4,'pak',1,1),  -- satuan & min = perkiraan
 ('BRG-031','Susu Kental Manis (Carnation)',1,'kaleng',1,2),  -- tertulis SKM / Carnetion; satuan & min = perkiraan
 ('BRG-032','Xoxolate Tea (Ruji)',3,'sachet',5,5),  -- ?? KONFIRMASI nama: tertulis XOXOLATE (mungkin Chocolate); satuan & min = perkiraan
 ('BRG-033','Ume Ume Tea (Ruji)',3,'sachet',26,5),  -- stok: 26 (bisa juga terbaca 25); satuan & min = perkiraan
 ('BRG-034','Earl Grey (Ruji)',3,'sachet',12,5),  -- satuan & min = perkiraan
 ('BRG-035','Black Sesame (Garnish)',4,'pak',1,1),  -- satuan & min = perkiraan
 ('BRG-036','Pistachio G (Garnish)',4,'pak',1,1),  -- ?? KONFIRMASI nama: tertulis PISTACIO G; satuan & min = perkiraan
 ('BRG-037','Milo Powder (Milo)',3,'pak',1,1),  -- satuan & min = perkiraan
 ('BRG-038','Soda CO2 (Zoda)',6,'tabung',0,1),  -- tertulis SODA lalu tulisan tangan CO2; satuan & min = perkiraan
 ('BRG-039','Tea B (Bendera)',3,'box',0,1),  -- kolom ORDER bertuliskan tangan, tidak terbaca; satuan & min = perkiraan
 ('BRG-040','Tea S (Sosro)',3,'box',3,2),  -- satuan & min = perkiraan
 ('BRG-041','Jugle (Polenghi)',4,'botol',4,2),  -- ?? KONFIRMASI nama: 'Lazy Lemon' dicoret, diganti tulisan tangan 'Jugle'; satuan & min = perkiraan
 ('BRG-042','Crumble (Caramba)',4,'pak',0,1),  -- satuan & min = perkiraan
 ('BRG-043','Jeruk Nipis (Pajak)',4,'kg',6,2),  -- satuan & min = perkiraan
 ('BRG-044','Cup Polos (SB)',5,'pak',2,2),  -- satuan & min = perkiraan
 ('BRG-045','Thermal (Starspex)',5,'roll',48,10),  -- stok: 48 (bisa juga terbaca 40); satuan & min = perkiraan
 ('BRG-046','Cleo Botol',6,'dus',5,2),  -- satuan & min = perkiraan
 ('BRG-047','Cup Panas',5,'pak',39,10),  -- stok: 39 (bisa juga terbaca 29); brand kosong di foto; satuan & min = perkiraan
 ('BRG-048','Straw Boba',5,'pak',1,1),  -- catatan tangan di kolom keterangan tidak terbaca; satuan & min = perkiraan
 ('BRG-049','Brown Sugar Liquid',2,'botol',4,2),  -- brand kosong di foto; satuan & min = perkiraan
 ('BRG-050','White Sesame',4,'pak',0,1);  -- brand kosong di foto; satuan & min = perkiraan

-- Pengguna awal (admin / karyawan) dibuat otomatis saat pertama kali membuka aplikasi.
