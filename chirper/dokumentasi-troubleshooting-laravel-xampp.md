# Dokumentasi Troubleshooting: Setup Laravel (Chirper) di XAMPP Windows

**Project:** Chirper (Laravel Tutorial - Getting Started)
**Lokasi:** `C:\xampp\htdocs\hbp\chirper`
**Tanggal:** Juli 2026

---

## Ringkasan

Dokumen ini mencatat proses troubleshooting saat pertama kali menjalankan project Laravel (tutorial Chirper) di lingkungan XAMPP Windows. Ada 4 masalah utama yang ditemui secara berurutan, masing-masing dengan penyebab dan solusi berbeda.

---

## Masalah 1: `could not find driver` (SQLite)

### Gejala
```
could not find driver (Connection: sqlite, Database: C:\xampp\htdocs\hbp\chirper\database\database.sqlite, ...)
```

### Penyebab
PHP yang dijalankan lewat `php artisan serve` bukan PHP dari XAMPP, melainkan instalasi PHP terpisah di `C:\Program Files\php-8.4.10\`, yang extension `pdo_sqlite` dan `sqlite3`-nya belum aktif di `php.ini`.

### Cara Diagnosis
```powershell
where php
php --ini
php -m | findstr -i sqlite
```
- `php --ini` menunjukkan `php.ini` mana yang benar-benar dipakai (`Loaded Configuration File`)
- `php -m | findstr -i sqlite` kosong = extension belum aktif

### Solusi
1. Buka file `php.ini` sesuai path dari `php --ini`
2. Cari baris berikut, hapus tanda `;` di depannya:
   ```ini
   extension=pdo_sqlite
   extension=sqlite3
   ```
3. Pastikan file `.dll`-nya ada di folder `ext\`:
   - `php_pdo_sqlite.dll`
   - `php_sqlite3.dll`
4. Tutup semua terminal, buka terminal baru, cek ulang:
   ```powershell
   php -m | findstr -i sqlite
   ```

---

## Masalah 2: MySQL XAMPP shutdown unexpectedly

### Gejala
```
[mysql] Error: MySQL shutdown unexpectedly.
```

### Kemungkinan Penyebab
1. **Port 3306 bentrok** dengan service MySQL/MariaDB lain
2. **File InnoDB corrupt** (`ibdata1`, `ib_logfile0`, `ib_logfile1`) — biasa terjadi setelah shutdown paksa
3. Ada **Windows Service MySQL lain** yang aktif (`MySQL80`, dll)
4. **Antivirus/permission** memblokir folder data MySQL

### Cara Diagnosis
```cmd
netstat -ano | findstr :3306
tasklist /FI "PID eq [nomor_PID]"
sc query MySQL80
```
Cek juga log di:
```
C:\xampp\mysql\data\mysql_error.log
```

### Solusi
- Kalau port bentrok: matikan proses yang pegang port 3306, atau ganti port MySQL XAMPP di `my.ini`
- Kalau file corrupt: backup folder `data`, hapus `ib_logfile0` & `ib_logfile1`, restart MySQL
- Kalau ada service lain: `net stop MySQL80`
- Jalankan XAMPP Control Panel **as Administrator**

**Status:** Resolved (root cause tidak dikonfirmasi ulang, tapi service berhasil jalan kembali)

---

## Masalah 3: PHP Version Mismatch (Composer Platform Check)

### Gejala
```
PHP Fatal error: Uncaught RuntimeException: Composer detected issues in your platform:
Your Composer dependencies require a PHP version ">= 8.4.1". You are running 8.2.12.
```

### Penyebab
Setelah PATH diarahkan ke PHP XAMPP (v8.2.12) untuk menyelesaikan Masalah 1, ternyata project Chirper **membutuhkan PHP ≥ 8.4.1** — yang tidak dipenuhi oleh PHP XAMPP.

### Insight Penting
Instalasi PHP 8.4.10 di `C:\Program Files\php-8.4.10\` yang tadinya dianggap "penyebab masalah" ternyata memang **PHP yang seharusnya dipakai** untuk project ini. Masalah sebenarnya dari awal adalah extension `pdo_sqlite` yang belum aktif di PHP 8.4.10 tersebut (lihat Masalah 1), bukan salah PHP-nya.

### Solusi
1. Buka **Environment Variables** → edit `Path`
2. Pindahkan `C:\Program Files\php-8.4.10` ke **atas** (prioritas lebih tinggi dari `C:\xampp\php`)
3. Aktifkan `pdo_sqlite` & `sqlite3` di `C:\Program Files\php-8.4.10\php.ini` (edit as Administrator)
4. Verifikasi:
   ```powershell
   php --ini
   # Loaded Configuration File: C:\Program Files\php-8.4.10\php.ini

   php -m | findstr -i sqlite
   # pdo_sqlite
   # sqlite3
   ```

---

## Masalah 4: `no such table: sessions`

### Gejala
```
SQLSTATE[HY000]: General error: 1 no such table: sessions
(Connection: sqlite, Database: C:\xampp\htdocs\hbp\chirper\database\database.sqlite, ...)
```

### Penyebab
Driver sudah aktif dan koneksi database berhasil, tapi migrasi database belum pernah dijalankan sehingga tabel `sessions` (dan tabel lain) belum ada.

### Solusi
```powershell
php artisan key:generate   # jika APP_KEY belum di-set
php artisan migrate
```

Jika file `database.sqlite` belum ada:
```powershell
New-Item "database\database.sqlite" -ItemType File
```

---

## Hasil Akhir

Setelah keempat masalah diselesaikan secara berurutan, aplikasi Laravel (Chirper) berhasil dijalankan dengan:
```powershell
php artisan serve
```
dan dapat diakses normal melalui `http://127.0.0.1:8000` atau `http://localhost:8000`.

---

## Catatan & Pelajaran

1. **Selalu cek `php --ini` dulu** sebelum edit `php.ini` — pastikan file yang diedit adalah file yang benar-benar dipakai (`Loaded Configuration File`), karena Windows bisa punya banyak instalasi PHP sekaligus.
2. **Urutan PATH di Windows menentukan PHP mana yang dipakai** — command `where php` dan `Get-Command php` berguna untuk verifikasi.
3. **Cek requirement PHP project** (lihat `composer.json` key `"require": {"php": "..."}`) sebelum menentukan PHP versi berapa yang harus diprioritaskan di PATH.
4. Setelah mengubah PATH atau `php.ini`, **wajib buka terminal baru** — perubahan tidak berlaku di terminal yang sudah terbuka.
5. Error `could not find driver` selalu terjadi **sebelum** proses koneksi ke database (terbukti dari "No queries executed" di halaman error Laravel), jadi bukan soal kredensial/konfigurasi database.
6. Setelah project bisa connect ke database, jangan lupa jalankan `php artisan migrate` sebelum mengakses halaman yang butuh tabel (seperti `sessions`).

---

## Command Reference Cepat

| Tujuan | Command |
|---|---|
| Cek PHP mana yang dipakai | `where php` atau `Get-Command php` |
| Cek php.ini yang aktif | `php --ini` |
| Cek extension aktif | `php -m \| findstr -i [nama_extension]` |
| Cek isi PATH terkait PHP | `$env:Path -split ';' \| Select-String "php"` |
| Cek port terpakai | `netstat -ano \| findstr :[port]` |
| Generate app key | `php artisan key:generate` |
| Jalankan migrasi | `php artisan migrate` |
| Jalankan server dev | `php artisan serve` |
