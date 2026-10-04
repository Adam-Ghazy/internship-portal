<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## Katalog lowongan portal

Katalog `/` mengambil lowongan `published` dari schema PostgreSQL `recruitment`,
diurutkan berdasarkan `published_at` terbaru. Detail tersedia di `/posisi/{slug}`;
lowongan yang belum dipublikasikan dan slug yang tidak ditemukan mengembalikan 404.

Isi data sintetis pada tabel yang sudah ter-migrate, tanpa migration baru atau reset:

```sh
docker compose exec -T app php artisan db:seed --class=PortalSeeder
```

Seeder memakai `updateOrCreate` dalam satu transaksi dan aman dijalankan ulang:
3 unit, 4 posisi/permintaan/lowongan, 1 periode, 1 program, 12 persyaratan,
3 tipe dokumen, dan 12 persyaratan dokumen. Satu akun pemohon sintetis yang
dinonaktifkan (`portal-seeder@example.invalid`) memenuhi FK `requester_id`;
akun ini bukan kredensial demo. Periode diperbarui relatif terhadap tanggal seed:
dibuka satu bulan sebelumnya dan ditutup sekitar dua bulan berikutnya. Tanggal
pelaksanaan berada di dalam periode tersebut, dengan durasi tiga bulan.

Judul FIN-03 dan COM-04 mengikuti spesifikasi baru: Analisis Keuangan dan
Komunikasi Korporat. Deskripsi, jurusan, dan jenjang memakai copy katalog lama;
COM-04 ditempatkan pada Teknologi Informasi agar data sintetis memakai tiga unit.

Tombol **Lamar posisi ini** menuju route `apply.create` (`/posisi/{slug}/lamar`)
yang dilindungi `auth:web`. Guest diarahkan ke login. Pelamar membuat atau melanjutkan
draft yang sama untuk setiap posisi; lamaran yang sudah dikirim diarahkan ke `/lamaran`.
Posisi non-published atau periode berakhir ditolak dengan pesan pendaftaran ditutup;
periode yang belum dibuka juga ditolak. Pemeriksaan berlaku pada seluruh aksi tulis.

`VacancyDocumentRequirement` memakai PK komposit `vacancy_id` + `document_type_id`.
Override pemilihan kunci mendukung `updateOrCreate`, `save`, dan `refresh` pada
pasangan tersebut; jangan menggunakan `find($id)` atau route binding satu ID.

Build aset lewat layanan Node yang tersedia:

```sh
docker compose run --rm --no-deps vite sh -c 'npm ci && npm run build'
```

## Alur lamaran pelamar

- `/lamaran` menampilkan lamaran milik `applicantProfile`; dashboard menautkan daftar ini.
  `/lamaran/{application}` menampilkan snapshot pengiriman, bukan profil yang terus berubah.
- POST `/lamaran/{application}/profil` menyimpan telepon dan 0..n pendidikan ke tabel
  profil/pendidikan sekaligus payload draft. Institusi berasal dari tabel `institutions`
  yang tidak diarsipkan; jenjang: SMA/SMK, D1, D2, D3, D4, S1, S2, S3. Nilai dan skala
  harus diisi berpasangan, maksimal dua desimal, dengan nilai tidak melebihi skala.
  Daftar institusi kosong tidak diisi otomatis; pendidikan boleh dikosongkan.
- POST `/lamaran/{application}/dokumen` menerima `document_type_id` dan `file` per tipe.
  Hanya PDF dengan MIME `application/pdf`, 1–5.242.880 byte, yang diterima. Berkas disimpan
  pada disk `local` di `storage/app/private/applications/{id}/{type}-{timestamp}.pdf`.
  SHA-256 dihitung dari isi berkas. Nama asli berada di payload draft, bukan nama path.
  `scan_status=clean` adalah **simulasi**, bukan pemeriksaan malware. Penggantian dokumen
  mengubah pointer draft; berkas lama tetap tersedia untuk riwayat pengiriman immutable.
- POST profil, dokumen, dan submit membawa CSRF dan `lock_version`. Simpan profil atau unggah menaikkan versi;
  versi usang ditolak dengan instruksi muat ulang. Kepemilikan, tahap, dan periode dicek
  ulang di dalam transaksi. Tombol unggah/kirim diblokir ketika profil belum disimpan.
- POST `/lamaran/{application}/submit` mensyaratkan profil tersimpan dan seluruh dokumen
  wajib. Transaksi membuat snapshot profil, pendidikan, posisi/unit/periode/kuota,
  menyalin referensi dokumen, lalu mengisi stage, submitted_at, dan current_submission_id.
  Revisi (`needs_revision`) membuat version_no berikutnya; pengiriman lama tidak berubah.
- Skema existing mewajibkan `public_reference NOT NULL` bahkan saat draft. Draft memakai
  nilai internal `DRAFT-<UUID>` yang tidak ditampilkan sebagai nomor lamaran. Saat submit
  pertama, nilainya diganti `APP-` + delapan karakter acak kapital; transaksi mengunci
  kandidat referensi dan memeriksa keunikannya. Nomor dipertahankan saat revisi.
  UNIQUE(profile,vacancy) tetap menjadi pagar terakhir untuk duplikasi.

Implementasi ini tidak membuat migration dan tidak mereset database. Verifikasi HTTP
memakai akun/institusi sintetis khusus, session login, tiga PDF, penolakan non-PDF dan
PDF >5 MB, konflik versi, submit, halaman snapshot, dan penolakan duplikat DB (23505).

### Tarik lamaran

- GET `/lamaran/{application}/tarik` menampilkan konfirmasi terpisah; POST pada URL
  yang sama membawa CSRF. Keduanya memerlukan login dan kepemilikan profil serta user;
  akses milik pelamar lain menghasilkan 404.
- Tahap `submitted`, `needs_revision`, dan `administrative_review` dapat ditarik.
  POST memeriksa ulang tahap dalam transaksi dengan penguncian row, lalu memanggil
  `DB::select('SELECT recruitment.withdraw_application(?, ?)', [$id, $actor])`.
  Tidak ada UPDATE langsung ke `applications` pada alur tarik.
- Draft yang belum pernah dikirim dihapus bersama `application_draft_documents` dan
  `application_drafts`, bukan diubah menjadi `withdrawn`. Berkas tersimpan tidak dihapus
  karena dapat dirujuk oleh arsip lain. Pelamar dapat membuat draft baru saat pendaftaran terbuka.
- Sukses mengarah ke `/lamaran` dengan pesan **Lamaran berhasil ditarik.** Tombol tarik
  hanya muncul untuk tahap yang diizinkan; `withdrawn` memakai badge netral.
  Detail tetap menampilkan snapshot, tanggal ditarik, dan daftar dokumen read-only.
- Fungsi DB existing bersifat idempoten untuk `withdrawn`; aplikasi sengaja menolak
  POST kedua dengan **Lamaran sudah ditarik dan tidak dapat ditarik kembali.** Tahap
  `manager_review`, `sm_review`, dan `decided` juga ditolak sebelum fungsi DB dipanggil.
- Lamaran withdrawn tetap menyimpan UNIQUE(profile,vacancy). Membuka kembali
  `/posisi/{slug}/lamar` tidak membuat row baru dan menampilkan **Anda sudah pernah
  mendaftar posisi ini. Lamaran ditarik dan tidak dapat dibuka kembali.**

Verifikasi nyata alur tarik memakai akun sintetis existing: application 1 berubah dari
`submitted` ke `withdrawn` melalui halaman konfirmasi dan POST; timestamp terisi,
POST kedua ditolak, daftar ulang tidak menambah row, draft uji terhapus, GET/POST
milik user lain 404, serta curl `/lamaran` dengan session 200. Application 3 tetap submitted.
Tidak ada migration baru, perubahan fungsi DB, atau reset database.

### Model lamaran dan akun staf uji

Model `Application`, `ApplicationSubmission`, `ApplicationReview`, `ApplicationDocument`,
dan `ApplicationEvent` menggunakan schema `recruitment` dan kolom database aktif.
`Application::currentSubmission` mengikuti `current_submission_id`; ketiga snapshot
di-cast ke array. Review memakai `acted_at` dan tidak mempunyai `updated_at`.
Event tidak memakai timestamp Eloquent; `save`/`delete` pada model ditolak. Jangan
menulis riwayat lewat query builder; mutasi riwayat merupakan tanggung jawab fungsi DB.

`PortalSeeder` juga membuat `manager@inka.test` dan `sm@inka.test`, password
`password` melalui cast hashed `RecruitmentUser`, dengan penugasan aktif masing-masing
`manager`/`sm` di Teknologi Informasi. Akun ini hanya untuk pengujian lokal.
Seed dua kali terverifikasi tetap menghasilkan dua akun dengan dua penugasan aktif.

Modul review staf belum tersedia: database aktif mempunyai kontrak berbeda dari
alur yang diminta. `complete_review` menerima `recommended/not_recommended` untuk
manager dan `accepted/rejected` untuk SM, membutuhkan tahap awal `manager_review`
serta pasangan review yang sudah ditugaskan. `publish_decision` mensyaratkan admin.
Tidak tersedia fungsi untuk inisialisasi review dari `submitted`. Implementasi
alur `approved/rejected` dan publikasi oleh SM menunggu database dengan kontrak
tersebut; tidak ada migration, perubahan fungsi DB, atau mutasi review manual.

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

You may also try the [Laravel Bootcamp](https://bootcamp.laravel.com), where you will be guided through building a modern Laravel application from scratch.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com/)**
- **[Tighten Co.](https://tighten.co)**
- **[WebReinvent](https://webreinvent.com/)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel/)**
- **[Cyber-Duck](https://cyber-duck.co.uk)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Jump24](https://jump24.co.uk)**
- **[Redberry](https://redberry.international/laravel/)**
- **[Active Logic](https://activelogic.com)**
- **[byte5](https://byte5.de)**
- **[OP.GG](https://op.gg)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
