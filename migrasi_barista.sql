-- Migrasi: tambah kolom nama barista pada barang masuk dan barang keluar.
-- Jalankan SEKALI saja pada database stok_nosta yang sudah ada (phpMyAdmin > pilih stok_nosta > tab SQL).
-- Data lama tidak hilang; baris lama akan menampilkan '-' pada kolom Barista.
-- Jika database baru diimpor dari database.sql versi terbaru, file ini TIDAK perlu dijalankan.
USE stok_nosta;
ALTER TABLE barang_masuk  ADD COLUMN nama_barista VARCHAR(100) NULL;
ALTER TABLE barang_keluar ADD COLUMN nama_barista VARCHAR(100) NULL;
