# Sistem Informasi Stok Barang - Nosta Coffee Heritage House

Aplikasi web (PHP + MySQL) untuk mencatat barang masuk dan keluar, menghitung stok otomatis,
stok opname, dan laporan. Sesuai rancangan pada proposal.

## Cara memasang (XAMPP, Windows)

1. Pasang XAMPP, lalu buka XAMPP Control Panel dan klik **Start** pada **Apache** dan **MySQL**.
2. Salin folder `stok-nosta` ini ke `C:\xampp\htdocs\`.
3. Buka `http://localhost/phpmyadmin`, pilih tab **Import**, pilih file `database.sql`, lalu klik **Import**.
   (Database `stok_nosta` dibuat otomatis.)
4. Buka `http://localhost/stok-nosta/` di peramban.
5. Jika MySQL Anda memakai password, ubah `DB_USER` dan `DB_PASS` di `config.php`.

## Akun awal (dibuat otomatis saat pertama kali dibuka)

| Peran    | Username   | Password     |
|----------|------------|--------------|
| Admin    | `admin`    | `admin123`   |
| Karyawan | `karyawan` | `karyawan123`|

**Segera ganti password** lewat menu Pengguna (khusus admin).

## Fitur

- **Login** dengan dua peran: Admin dan Karyawan.
- **Dashboard**: jumlah barang, transaksi hari ini, barang di bawah stok minimum, aktivitas terakhir.
- **Data Barang**: cari, tambah, ubah, hapus (admin). Badge status: Aman, Menipis (di bawah minimum), Habis.
- **Barang Masuk**: stok bertambah otomatis. **Barang Keluar**: stok berkurang otomatis, ditolak jika melebihi stok.
- **Stok Opname**: karyawan/admin memasukkan stok fisik, sistem menampilkan selisih,
  admin menyetujui atau menolak penyesuaian.
- **Laporan** (admin): stok, barang masuk, barang keluar, stok opname per periode.
  Klik **Cetak / Simpan PDF** lalu pilih "Simpan sebagai PDF" di jendela cetak peramban.
- **Kategori, Pemasok, Pengguna** (admin).

| Menu | Admin | Karyawan |
|------|:-----:|:--------:|
| Data barang | kelola | lihat |
| Barang masuk / keluar | ya | ya |
| Stok opname (input) | ya | ya |
| Setujui penyesuaian stok | ya | tidak |
| Laporan, kategori, pemasok, pengguna | ya | tidak |

## Data barang awal

`database.sql` berisi **50 barang** yang disalin dari lembar stok kafe (Jumat, 13 November), dikelompokkan
dalam 6 kategori, plus 2 pemasok contoh. Nama barang berformat "Item (Brand)". Stok awal = angka kolom STOCK.
**Satuan dan stok minimum adalah perkiraan** (tidak ada di lembar stok); koreksi lewat menu Data Barang.
Baris bertanda `?? KONFIRMASI` di dalam `database.sql` perlu dicek ulang terhadap lembar aslinya.
Barang yang sudah punya transaksi tidak bisa dihapus.

### Nama barista pada barang masuk dan keluar
Form Barang Masuk dan Barang Keluar punya isian wajib **Barista** (yang memasukkan / yang mengambil barang).
Untuk akun karyawan, isiannya terisi otomatis dengan nama akun dan bisa diganti. Riwayat dan laporan menampilkan
kolom **Barista** (nama yang diisi) dan **Dicatat oleh** (akun yang login). Database lama yang dibuat sebelum fitur ini
perlu menjalankan `migrasi_barista.sql` satu kali di phpMyAdmin (tab SQL).

### Impor ulang database
Jika database `stok_nosta` sudah ada, hapus dulu di phpMyAdmin (pilih `stok_nosta` > tab **Operations** >
**Delete the database**), lalu impor `database.sql` lagi. Perhatian: ini menghapus semua transaksi dan akun lama.

## Membuka dari HP (satu Wi-Fi dengan laptop)

Cari alamat IP laptop (`ipconfig`), lalu buka `http://<IP-laptop>/stok-nosta/` di HP.
Jika tidak bisa, izinkan Apache di Windows Firewall.

## Cadangan data

Di phpMyAdmin pilih database `stok_nosta`, tab **Export**, lalu simpan file `.sql` secara berkala.

## Struktur file

```
config.php            pengaturan database
database.sql          struktur tabel + data contoh
style.css             tampilan (tema hijau lumut dan cokelat, responsif, cetak)
assets/               logo Nosta (logo.png, logo-light.png), favicon, logo asli
login.php logout.php  masuk dan keluar
index.php             dashboard
barang.php            data barang
barang_masuk.php      barang masuk
barang_keluar.php     barang keluar
stok_opname.php       stok opname dan persetujuan
laporan.php           laporan dan cetak
kategori.php pemasok.php pengguna.php
includes/             init (koneksi, keamanan), header (sidebar), footer, icons (ikon SVG offline)
```

## Keamanan yang sudah diterapkan

Password disimpan dengan hash, semua query memakai prepared statement, token CSRF di semua formulir,
keluaran di-escape (anti XSS), pembatasan percobaan login, dan hak akses per peran.
Untuk dipakai di internet, pasang HTTPS dan ganti password bawaan.

## Daftar uji cepat setelah dipasang

1. Login sebagai `admin`, tambah satu barang baru dengan stok awal 10.
2. Barang Masuk +5, stok menjadi 15. Barang Keluar 3, stok menjadi 12. Barang Keluar 100 harus ditolak.
3. Stok Opname: isi stok fisik 11, lalu Setujui sebagai admin. Stok menjadi 11.
4. Laporan Stok periode bulan ini: Akhir = 11.
5. Login sebagai `karyawan`: menu Laporan dan Pengguna tidak muncul, tombol Setujui tidak ada.
