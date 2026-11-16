# Ruang Magang — Portal Magang INKA

Portal rekrutmen magang berbasis Laravel 10, Blade, Tailwind CSS, dan PostgreSQL.
Pelamar dapat melihat lowongan, menyimpan draf, mengunggah dokumen, mengirim
lamaran, dan melihat hasil setelah dipublikasikan. Staf mengelola penugasan
review, penilaian manager/SM, publikasi keputusan, dan akun staf sesuai perannya.

## Menjalankan secara lokal

Kebutuhan: PHP 8.2 dengan ekstensi Laravel dan `pdo_pgsql`, Composer 2,
PostgreSQL 16, serta Node.js 20 dan npm. File Docker merupakan konfigurasi lokal
operator dan tidak disimpan di repositori; langkah berikut tidak memerlukannya.

1. Siapkan database PostgreSQL kosong bernama `internship_portal` dan pengguna
   `internship` dengan kata sandi lokal. Untuk instalasi baru, pengguna migrasi
   harus dapat membuat schema, tabel, fungsi PL/pgSQL, dan trigger.
2. Pasang dependensi dan salin konfigurasi:

   ```sh
   composer install
   cp .env.example .env
   php artisan key:generate
   ```

3. Sesuaikan `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, dan `DB_PASSWORD`
   pada `.env`. Contoh menggunakan PostgreSQL lokal pada port 5432.
   Tabel domain memakai nama lengkap `recruitment.*`; migrasi membuat schema
   tersebut. Koneksi PostgreSQL tetap memakai `search_path=public` dari
   `config/database.php`; tidak ada variabel `DB_SCHEMA` yang perlu diisi.
4. Jalankan migrasi pada database baru, lalu bangun aset:

   ```sh
   php artisan migrate
   npm ci
   npm run build
   php artisan serve
   ```

Buka `http://localhost:8000`. Untuk pengembangan aset gunakan `npm run dev`
di terminal terpisah. Direktori `storage/` dan `bootstrap/cache/` harus dapat
ditulis oleh proses PHP. `public/build/` tidak dilacak Git dan perlu dibangun
setelah clone atau perubahan aset.

Untuk database yang sudah berisi data, buat cadangan dan periksa
`php artisan migrate:status` sebelum menerapkan migrasi. Jangan memakai
`migrate:fresh` sebagai cara memperbarui instalasi: schema `recruitment` terpisah
dan reset dapat menghapus data atau meninggalkan objek yang berbenturan.

### Data demo (lokal saja)

```sh
php artisan db:seed --class=PortalSeeder
```

Seeder mengisi katalog sintetis dan akun berikut. Login menerima username atau
email; kata sandi demo sama dengan username.

| Peran | Username | Email |
| --- | --- | --- |
| Admin | `admin` | `admin@inka.test` |
| Manager | `managerSDM` | `manager@inka.test` |
| Senior manager | `smSDM` | `sm@inka.test` |
| Pelamar | `bagas` | `bagas@inka.test` |

Manager dan SM demo ditugaskan ke unit Teknologi Informasi. Menjalankan ulang
seeder memperbarui katalog dan periode pendaftaran, tetapi mempertahankan kata
sandi serta status akun yang sudah ada. Seeder ditolak di luar lingkungan
`local` dan `testing`. Daftar institusi pendidikan dikelola terpisah
dan tidak otomatis diisi oleh seeder ini.

## Kontrak alur aplikasi

- Katalog publik berada di `/`, detail di `/posisi/{slug}`. Pendaftaran memerlukan
  akun pelamar dan lowongan terbit dalam periode yang masih terbuka.
- Draf memakai `lock_version` untuk menolak penyimpanan dari tab yang usang.
  Pengiriman menyimpan snapshot profil, pendidikan, posisi, dan referensi dokumen;
  perubahan profil berikutnya tidak mengubah snapshot yang sudah dikirim.
- Admin menugaskan manager dan SM berbeda pada unit terkait. Manager memberi
  `recommended`/`not_recommended`; SM memberi `accepted`/`rejected`. Keduanya
  memerlukan catatan. Admin memublikasikan keputusan; catatan internal tidak
  ditampilkan kepada pelamar.
- Mutasi review, publikasi, dan penarikan lamaran menggunakan fungsi PostgreSQL
  pada schema `recruitment`. Pertahankan kontrak fungsi, penguncian, dan trigger
  immutable ketika mengubah kode; jangan menggantinya dengan update langsung.
- Unggahan dibatasi PDF maksimal 5 MB dan disimpan pada disk `local` di
  `storage/app/private/applications/`, bukan direktori publik. Nilai
  `scan_status=clean` saat ini **simulasi**, bukan hasil pemindaian malware.
- Publikasi keputusan menulis event ke tabel outbox, tetapi pemroses outbox,
  pengiriman notifikasi asinkron, dan worker aplikasi belum diimplementasikan.
  Mengubah `QUEUE_CONNECTION` saja tidak mengaktifkan fitur tersebut.

## Pembersihan dokumen

Jalankan `php artisan applications:prune-files` untuk melihat berkas lokal tanpa
referensi yang berumur lebih dari 48 jam. Perintah ini tidak menghapus apa pun
secara default. Setelah hasil diperiksa, tambahkan `--execute` untuk menghapus.
Opsi `--hours` minimal 24; referensi draf, pengiriman, dan dokumen grup tetap
dipertahankan. Jadwalkan melalui scheduler deployment bila diperlukan. Berkas
yang tidak pernah tercatat di database tidak dipindai oleh perintah ini.

## Pengujian tanpa menyentuh data operasional

Gunakan database PostgreSQL khusus, misalnya `internship_portal_test`, dengan
pengguna yang hanya diberi akses ke database pengujian. Jangan gunakan database
pengembangan bersama, staging, atau produksi. SQLite bukan pengganti untuk
pengujian fungsi, trigger, dan schema PostgreSQL proyek ini.

Siapkan database kosong tersebut, lalu jalankan dari shell khusus pengujian:

```sh
export APP_ENV=testing
export DB_CONNECTION=pgsql
export DB_HOST=127.0.0.1
export DB_PORT=5432
export DB_DATABASE=internship_portal_test
export DB_USERNAME=internship_test
export DB_PASSWORD='kata-sandi-database-uji'
unset DATABASE_URL
php artisan config:clear
php artisan migrate --env=testing
php artisan test
```

Periksa kembali target koneksi sebelum migrasi/test. `APP_ENV=testing` tidak
mengisolasi database dengan sendirinya. Jangan menjalankan pengujian memakai
cache konfigurasi produksi. `phpunit.xml` menggunakan cache/session array,
mailer array, dan queue sinkron; pastikan konfigurasi test tetap terisolasi
ketika menambah test yang menulis data. Jangan menjalankan reset database
operasional untuk memperbaiki test.

## Checklist deployment

- Gunakan `APP_ENV=production`, **`APP_DEBUG=false`**, `APP_URL` HTTPS yang benar,
  `LOG_LEVEL=warning` atau kebijakan log operasional, dan
  `SESSION_SECURE_COOKIE=true`. Atur terminasi TLS/proxy agar Laravel mengenali
  HTTPS dengan benar.
- Simpan `.env` dan rahasia di luar Git. Gunakan `APP_KEY` tetap untuk instalasi
  tersebut; jangan membuat ulang key setiap deploy. Gunakan kredensial database
  produksi terpisah dari lokal dan pengujian, serta koneksi TLS sesuai
  konfigurasi PostgreSQL lingkungan deployment.
- Arahkan document root web server ke `public/`, bukan root repositori.
  Sesuaikan batas unggahan PHP/web server untuk PDF 5 MB beserta overhead request.
  Jangan mengekspos `storage/app/private/` melalui symlink atau web server.
- Pasang dependensi dengan `composer install --no-dev --optimize-autoloader`
  dan bangun aset dengan `npm ci && npm run build` pada tahap build.
  Terapkan migrasi yang sudah ditinjau dengan `php artisan migrate --force`
  setelah pencadangan. Jangan menjalankan seeder demo.
- Setelah konfigurasi final terpasang, jalankan `php artisan config:cache`,
  `php artisan route:cache`, dan `php artisan view:cache`. Pastikan proses PHP
  dapat menulis direktori runtime tanpa membuat seluruh repositori writable.
- Default cache dan session berupa file cocok untuk satu instance. Untuk banyak
  instance, gunakan penyimpanan cache/session bersama, misalnya Redis dengan
  `CACHE_DRIVER=redis` dan `SESSION_DRIVER=redis`. Redis opsional, memerlukan
  layanan Redis serta ekstensi `phpredis` (default `REDIS_CLIENT`); isolasikan
  prefix/database per lingkungan. Jangan memilih driver database tanpa
  menyiapkan tabel pendukungnya.
- Pertahankan `QUEUE_CONNECTION=sync` sampai ada implementasi job/worker yang
  benar-benar digunakan. SMTP dan variabel queue bukan bukti bahwa notifikasi
  outbox telah terkirim; pantau event yang belum diproses.
- Cadangkan PostgreSQL dan berkas privat secara konsisten. Pada banyak instance,
  direktori unggahan harus tersedia bersama; mengubah `FILESYSTEM_DISK` saja
  tidak memindahkan unggahan karena kode saat ini memilih disk `local` langsung.
- Integrasikan pemindai malware nyata sebelum menganggap status dokumen sebagai
  hasil pemeriksaan keamanan. Uji login, otorisasi staf, unggah, submit, review,
  publikasi, dan akses dokumen setelah deployment.
