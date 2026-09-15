# LAPORAN AUDIT SISTEM & ROOT CAUSE BUG (BUG_REPORT.md)

**Aplikasi:** Resort Web QR Voucher Management System  
**Repository / Branch:** `without-redis`  
**Tanggal Audit:** 04 September 2026  
**Status Laporan:** *Menunggu Review & Keputusan User (Belum Ada Tindakan Perbaikan Langsung)*

---

## 1. Ringkasan Eksekutif

Audit menyeluruh telah dilakukan pada seluruh modul utama aplikasi (*Voucher Lifecycle, Scanning & Redemption, PMS Booking Import, WhatsApp Delivery, Database Constraints & Soft Deletes, Role & Permission, serta Reporting*). 

Ditemukan sejumlah **akar masalah (root cause)** mulai dari tingkat **High/Critical Operational** yang secara langsung mengganggu operasional staf di lapangan, hingga **Medium/Low** yang berdampak pada integritas data, performa, dan keamanan multi-properti.

Dokumen ini disusun secara terperinci agar dapat ditinjau terlebih dahulu oleh manajemen/pengguna sebelum tindakan perbaikan teknis dieksekusi.

---

## 2. Matriks Ringkasan Temuan Bug

| ID | Kategori / Modul | Judul Masalah & Gejala | Keparahan | Status Saat Ini |
|---|---|---|---|---|
| **BUG-01** | Voucher / Scan QR | Gagal scan QR Sarapan di pagi hari checkout: *"This voucher has no remaining one-time facility quota during the checkout grace period"* | **HIGH (Blocker)** | Teridentifikasi (Penyebab ditemukan) |
| **BUG-02** | Import Booking | Semua data import di `/bookings-import` otomatis menjadi `check_in` dan QR Voucher terbit prematur | **HIGH (Blocker)** | Teridentifikasi (Penyebab ditemukan) |
| **BUG-03** | Console / Cronjob | Inkonsistensi jam kadaluarsa voucher: `DailyMaintenance` (12:30 WIB) vs `ExpireVouchers` / `VoucherService` (21:00 WIB) | **HIGH** | Teridentifikasi |
| **BUG-04** | Import Booking | Tidak ada pengecekan ketersediaan kamar (*Room Double-Booking*) pada import CSV/Excel | **HIGH** | Teridentifikasi |
| **BUG-05** | Database / Soft Delete | Crash SQL 1062 Duplicate Entry saat membuat/mengimpor booking dengan kode referensi yang pernah di-soft delete | **HIGH** | Teridentifikasi |
| **BUG-06** | WhatsApp Delivery | Panggilan HTTP API eksternal (Fonnte/Whacenter) dieksekusi di dalam Database Transaction & Row Lock | **HIGH** | Teridentifikasi |
| **BUG-07** | WhatsApp Delivery | Hardcoded `countryCode: 62` merusak pengiriman pesan WhatsApp ke tamu mancanegara (Malaysia, Singapura, dll) | **MEDIUM** | Teridentifikasi |
| **BUG-08** | WhatsApp Delivery | Pengiriman gambar QR Whacenter gagal diam-diam (*silent fail*) pada host lokal / non-HTTPS | **MEDIUM** | Teridentifikasi |
| **BUG-09** | Auth / Role Management | Role master `super-admin` dapat dihapus atau diubah namanya oleh pengguna dengan izin `roles.manage` | **MEDIUM** | Teridentifikasi |
| **BUG-10** | Multi-Property Security | Laporan Scan QR (`QrScanLogController`) dan Log WhatsApp (`DeliveryLogController`) tidak memiliki *property scoping* | **MEDIUM** | Teridentifikasi |
| **BUG-11** | Voucher / Kuota | Inkonsistensi logika masa berlaku penambahan pax (*Add-on Pax*) antara header voucher, penukaran, dan laporan | **MEDIUM** | Teridentifikasi |
| **BUG-12** | Reporting / Performa | Query laporan penukaran tamu me-load seluruh tabel voucher ke memori PHP (*Potential Memory Exhaustion*) | **MEDIUM** | Teridentifikasi |
| **BUG-13** | Database / Guest | Duplikasi data tamu (`guests`) saat import PMS jika kolom email kosong | **LOW** | Teridentifikasi |
| **BUG-14** | Import UX / Logging | Indeks nomor baris spreadsheet tidak tercatat pada log kegagalan import (`Row: N/A`) | **LOW** | Teridentifikasi |
| **BUG-15** | Authorization | Ketiadaan permission terpisah untuk Edit dan Hapus Booking (`bookings.edit` & `bookings.delete`) | **LOW** | Teridentifikasi |

---

## 3. Rincian Masalah yang Baru Terjadi (Operational Blockers)

### BUG-01: Gagal Scan QR Sarapan di Pagi Hari Checkout
- **File Terdampak:**
  - `app/Models/GuestVoucher.php` (Metode: `isOneTimeGracePeriodActive`)
  - `app/Services/VoucherService.php` (Baris 324–334)
  - `app/Http/Controllers/VoucherController.php` (Baris 475–489)
  - `app/Http/Controllers/Api/VoucherApiController.php` (Baris 311–325)
- **Tingkat Keparahan:** **HIGH (Operational Blocker)**
- **Gejala / Error:**
  Saat staf restoran melakukan scan QR Voucher tamu untuk fasilitas Breakfast pada pagi hari tanggal checkout (misal jam 08:30 WIB), muncul pesan error:
  > *"This voucher has no remaining one-time facility quota during the checkout grace period."*
  Padahal tamu belum checkout, kuota sarapan masih ada, dan jam operasional sarapan sedang berlangsung.
- **Root Cause (Akar Masalah):**
  Logika penentuan masa tenggang (*grace period*) pada `GuestVoucher.php`:
  ```php
  // Logika bermasalah sebelum perbaikan:
  if ($now->toDateString() === $checkOutDate) {
      return $now->lte($extendedCutoff);
  }
  ```
  Pada pagi hari tanggal checkout (misalnya pk 08:00–12:30 WIB), tanggal saat ini sama dengan tanggal checkout, dan jam saat ini masih lebih kecil dari batas perpanjangan (`$extendedCutoff` = 13:30 WIB).
  Akibatnya, fungsi `isOneTimeGracePeriodActive()` bernilai **`TRUE`** untuk tamu yang **masih aktif menginap**.
  
  Ketika `isOneTimeGrace` bernilai `TRUE`, alur verifikasi dan penukaran voucher di `VoucherController` dan `VoucherService` langsung mengaktifkan filter khusus:
  ```php
  if ($isOneTimeGrace) {
      $facilityStatuses = $facilityStatuses->filter(fn($f) => $f->is_one_time)->values();
  }
  ```
  Karena fasilitas sarapan (*Breakfast*) **bukan fasilitas one-time** (`is_one_time = false`), sarapan langsung disingkirkan dari daftar fasilitas yang valid hari itu. Sistem kemudian menganggap kuota fasilitas habis dan melemparkan exception error grace period.
- **Dampak Bisnis:**
  Tamu hotel yang berhak menikmati sarapan sebelum checkout ditolak oleh sistem di restoran, menyebabkan komplain tamu dan staf harus melakukan pencatatan manual.
- **Rekomendasi Solusi:**
  Pisahkan dengan tegas dua kondisi pada `GuestVoucher::isOneTimeGracePeriodActive()`:
  1. **Jika tamu SUDAH checkout:** Grace period aktif sejak menit tamu checkout hingga batas perpanjangan (`checked_out_at` s/d `extendedCutoff`).
  2. **Jika tamu BELUM checkout:** Status stay masih normal. Grace period fasilitas one-time **HANYA** boleh aktif setelah melewati jam checkout reguler (`$now->gte($cutoff) && $now->lte($extendedCutoff)`).

---

### BUG-02: Import Booking di `/bookings-import` Otomatis Mengubah Status Jadi `check_in` dan Menerbitkan Voucher Prematur
- **File Terdampak:**
  - `app/Imports/BookingsImport.php` (Baris 170–172, 235–256)
  - `app/Http/Controllers/BookingController.php` (Baris 188–220)
- **Tingkat Keparahan:** **HIGH (Operational Blocker)**
- **Gejala / Error:**
  Saat staf mengunggah file Excel/CSV reservasi PMS melalui halaman `/bookings-import`, semua data reservasi baru langsung berstatus `check_in`. Padahal tamu baru akan tiba beberapa hari ke depan. Staf front desk kehilangan kendali untuk melakukan proses check-in manual ketika tamu tiba di resort.
- **Root Cause (Akar Masalah):**
  1. Pada `BookingsImport.php`, terdapat pemetaan status yang mengelompokkan kata `'confirmed'` dan angka `'1'` ke status `BookingStatus::CheckIn`:
     ```php
     // Kode bermasalah:
     if (in_array($status, ['confirmed', 'checked in', 'in-house', '1'])) {
         $data['status'] = BookingStatus::CheckIn->value;
     }
     ```
     Pada format ekspor PMS hotel standar (seperti Sirvoy, Cloudbeds, Opera, Guesty), hampir semua reservasi yang sah dan belum tiba berstatus **"Confirmed"** atau bernilai **"1"**. Kode ini memaksa seluruh reservasi confirmed menjadi `CheckIn`.
  2. Di baris 170:
     ```php
     if ($status === BookingStatus::CheckIn) {
         $this->checkedInBookings[] = $booking;
     }
     ```
     Semua booking yang berubah menjadi `CheckIn` dimasukkan ke dalam antrean `$checkedInBookings`.
  3. Di `BookingController.php`:
     ```php
     foreach ($checkedInBookings as $booking) {
         $this->vouchers->generateForBooking($dbBooking);
     }
     ```
     Sistem langsung membuat dan mengaktifkan QR voucher serta memicu pengiriman WhatsApp otomatis untuk tamu yang belum sampai di hotel.
- **Dampak Bisnis:**
  - Status kamar di resort menjadi berantakan (semua kamar terdeteksi terisi/in-house).
  - Tamu menerima link QR voucher berhari-hari sebelum tanggal kedatangan mereka.
  - Front desk tidak dapat membedakan tamu yang sudah benar-benar berada di properti vs yang masih dalam perjalanan.
- **Rekomendasi Solusi:**
  - Ubah pemetaan status reservasi: `'confirmed'`, `'pending'`, dan angka `'1'` harus dipetakan ke `BookingStatus::ExpectedArrival`.
  - Hanya string eksplisit seperti `'check in'`, `'checked in'`, `'in-house'`, `'inhouse'` yang dipetakan ke `BookingStatus::CheckIn`.
  - Biarkan staf front office menekan tombol *Check In* secara manual saat tamu hadir di lobi.

---

## 4. Temuan Bug Latent Kritis & Tinggi (Critical & High Severity)

### BUG-03: Inkonsistensi Jam Kadaluarsa Voucher Antara `DailyMaintenance` (12:30 WIB) dan `ExpireVouchers` / `VoucherService` (21:00 WIB)
- **File Terdampak:**
  - `app/Console/Commands/DailyMaintenance.php` (Baris 202–215)
  - `app/Console/Commands/ExpireVouchers.php` (Baris 80–92)
  - `app/Services/VoucherService.php` (Baris 702–716)
  - `app/Http/Controllers/VoucherController.php` (Baris 446)
  - `routes/console.php` (Baris 12–16)
- **Tingkat Keparahan:** **HIGH**
- **Akar Masalah:**
  Terdapat dua definisi berbeda mengenai kapan sebuah voucher checkout kedaluwarsa:
  1. Pada `DailyMaintenance::shouldExpire()`, waktu kedaluwarsa dihitung berdasarkan `maintenance.checkout_cutoff` (default **12:30 WIB**):
     ```php
     $cutoffTime = Setting::get('maintenance.checkout_cutoff', '12:30');
     $checkOutDate = Carbon::parse($voucher->booking->check_out)
         ->setTimeFromTimeString($cutoffTime); // 12:30 WIB!
     ```
  2. Pada `ExpireVouchers::shouldExpire()`, `VoucherService::checkAndExpireIfNeeded()`, dan `VoucherController::verifyScannedCode()`, waktu kedaluwarsa ditetapkan pada **21:00 WIB (9 PM)** di hari checkout:
     ```php
     $checkOutDate = Carbon::parse($voucher->booking->check_out->toDateString(), $timezone)
         ->setTime(21, 0, 0); // 21:00 WIB!
     ```
  3. Pada jadwal cronjob `routes/console.php`:
     ```php
     Schedule::command('daily:maintenance --all')
         ->dailyAt('12:35')
         ->timezone('Asia/Jakarta');
     ```
  Ketika jam 12:35 WIB tiba, cronjob `daily:maintenance --all` berjalan dan langsung mengeksekusi `runExpireVouchers()`. Karena jam 12:35 sudah melewati jam 12:30, **seluruh voucher tamu checkout hari itu langsung diubah statusnya menjadi `Expired` pada jam 12:35 WIB (atau 13:35 WIB setelah 1 jam grace period)**!
- **Dampak Bisnis:**
  Aturan bisnis dan ekspektasi manajemen resort menyatakan voucher berlaku hingga pukul 21:00 WIB di hari checkout agar tamu masih bisa menikmati fasilitas sore/malam sebelum meninggalkan area resort. Namun karena `DailyMaintenance`, voucher mati secara prematur pada siang hari.
- **Rekomendasi Solusi:**
  Samakan logika di `DailyMaintenance::shouldExpire()` agar menggunakan jam 21:00 WIB atau panggil langsung metode terpusat `VoucherService::checkAndExpireIfNeeded()`.

---

### BUG-04: Tidak Ada Pengecekan Ketersediaan Kamar (Room Double-Booking) pada Import PMS
- **File Terdampak:**
  - `app/Imports/BookingsImport.php` (Metode: `model`)
  - `app/Services/BookingService.php` (Metode: `assertRoomAvailable`)
- **Tingkat Keparahan:** **HIGH**
- **Akar Masalah:**
  Pada pembuatan dan pengeditan reservasi manual melalui UI Web (`BookingService::create` dan `BookingService::updateBooking`), sistem selalu memanggil `assertRoomAvailable()` untuk mencegah dua tamu menempati kamar yang sama di tanggal yang saling bertabrakan (*overlapping dates*).
  
  Namun, pada `BookingsImport.php`, proses import langsung menginstansiasi objek `new Booking([...])` dan menyimpannya ke database tanpa melakukan validasi ketersediaan kamar sama sekali.
- **Dampak Bisnis:**
  Jika staf mengunggah file CSV dari dua channel penjualan yang berbeda (misal OTA A dan OTA B) yang berisi reservasi bentrok untuk kamar yang sama, sistem akan menerima keduanya tanpa peringatan. Dua tamu berbeda akan dialokasikan ke kamar fisik yang sama pada tanggal yang sama.
- **Rekomendasi Solusi:**
  Tambahkan pengecekan tumpang tindih kamar pada `BookingsImport::model()` sebelum record disimpan. Jika terdeteksi bentrok tanggal dengan booking aktif lain, lemparkan kegagalan baris ke daftar `$failures`.

---

### BUG-05: Unhandled SQL 1062 Duplicate Entry pada Booking yang Pernah Di-Soft Delete
- **File Terdampak:**
  - `chanayac_resort_web_qr.sql` (Tabel `bookings`, baris 882–883)
  - `app/Imports/BookingsImport.php` (Baris 52–59)
  - `app/Http/Requests/StoreBookingRequest.php` (Baris 26–27)
  - Bukti historis error tercatat di database: `database/seeders/data.sql:886`
- **Tingkat Keparahan:** **HIGH**
- **Akar Masalah:**
  Pada tabel `bookings`, kolom `reference` dan `booking_code` memiliki indeks unik MySQL:
  ```sql
  UNIQUE KEY `bookings_reference_unique` (`reference`),
  UNIQUE KEY `bookings_booking_code_unique` (`booking_code`),
  ```
  Fitur *Soft Deletes* (`deleted_at`) diterapkan pada model `Booking`.
  Dalam MySQL InnoDB, indeks unik standar **tetap memeriksa baris data yang memiliki nilai `deleted_at IS NOT NULL`**.
  
  Di sisi aplikasi (`BookingsImport.php:52`):
  ```php
  $existing = Booking::where('reference', $row['reference'])->first();
  ```
  Query Eloquent di atas secara default menyisipkan klausa `WHERE deleted_at IS NULL`. Jika suatu booking pernah dihapus (soft-deleted), `$existing` bernilai `null`. Import kemudian mengeksekusi query `INSERT` dengan reference lama tersebut, yang seketika ditolak oleh MySQL dengan error fatal:
  `SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry '1153-FC1' for key 'bookings_reference_unique'`.
  Seluruh baris import berikutnya terhenti atau menghasilkan kegagalan fatal.
- **Dampak Bisnis:**
  Jika reservasi pernah dibatalkan/dihapus lalu diimpor kembali, proses import gagal total dan sistem memunculkan error server 500.
- **Rekomendasi Solusi:**
  1. Pada `BookingsImport`, gunakan `Booking::withTrashed()->where('reference', ...)->first()`. Jika data lama berstatus trashed, lakukan restore/update atau generate suffix unik baru.
  2. Tambahkan validasi `Rule::unique('bookings', 'reference')->withoutTrashed()` pada form request input booking.

---

### BUG-06: Panggilan HTTP API WhatsApp Eksternal di Dalam Database Transaction & Row Lock
- **File Terdampak:**
  - `app/Services/VoucherDeliveryService.php` (Baris 314–360)
- **Tingkat Keparahan:** **HIGH**
- **Akar Masalah:**
  Di dalam metode `VoucherDeliveryService::sendPendingLogs()`, proses pengiriman pesan ke provider WhatsApp dilakukan di dalam transaksi database dengan penguncian baris pesimistik:
  ```php
  foreach ($pendingLogs as $log) {
      DB::transaction(function () use ($log) {
          $lockedLog = DeliveryLog::query()->lockForUpdate()->find($log->id);
          // ...
          // PANGGILAN NETWORK EKSTERNAL DI DALAM LOCK:
          $result = $this->sender()->send(
              $lockedLog->phone_number,
              $lockedLog->message_content,
              $lockedLog->qr_path,
              $guestName,
              $qrLocalPath
          );
          // ...
      });
  }
  ```
  Permintaan HTTP ke server pihak ketiga (Fonnte atau Whacenter) dapat mengalami latensi jaringan, antrean gateway, atau timeout 10–30 detik per permintaan.
  Menahan `DB::transaction` dan `lockForUpdate()` selama I/O jaringan eksternal menyebabkan koneksi database pool cepat habis, tabel terkunci, dan memicu *database lock wait timeout exceeded* atau deadlock jika ada proses check-in lain yang berjalan bersamaan.
- **Dampak Bisnis:**
  Aplikasi bisa mendadak lambat (*freeze*) atau error saat cronjob delivery berjalan bersamaan dengan staf yang sedang melayani tamu di front desk.
- **Rekomendasi Solusi:**
  Ubah status log menjadi `'processing'` dalam transaksi singkat, lepas transaksi dan lock database, lakukan panggilan HTTP WhatsApp di luar transaksi, lalu update status akhir (`'sent'` atau `'failed'`) dalam transaksi singkat berikutnya.

---

## 5. Temuan Bug Menengah (Medium Severity)

### BUG-07: Hardcoded `countryCode: 62` Merusak Pengiriman WhatsApp ke Nomor Internasional
- **File Terdampak:**
  - `app/Services/FonnteService.php` (Baris 69, 76, 148–164)
- **Tingkat Keparahan:** **MEDIUM**
- **Akar Masalah:**
  Pada `FonnteService.php`, payload permintaan pengiriman selalu menyertakan:
  ```php
  ['name' => 'countryCode', 'contents' => '62'],
  // atau
  'countryCode' => '62',
  ```
  Jika tamu berasal dari luar negeri (seperti Malaysia `+6011...`, Singapura `+6588...`, Inggris `+4473...` yang terbukti ada di data riil resort), Fonnte akan menganggap nomor tersebut nomor lokal Indonesia dan menambahkan prefiks `62` di depannya (misal menjadi `626011...`).
- **Dampak Bisnis:**
  Pesan WhatsApp berisi voucher digital gagal terkirim ke tamu wisatawan asing.
- **Rekomendasi Solusi:**
  Periksa apakah nomor telepon tamu diawali dengan kode negara non-62. Jika ya, kosongkan parameter `countryCode` dan kirim nomor internasional lengkap sesuai spesifikasi API Fonnte.

---

### BUG-08: Pengiriman Gambar QR Whacenter Gagal Diam-Diam (*Silent Fail*) pada Host Lokal / Non-HTTPS
- **File Terdampak:**
  - `app/Services/WhacenterService.php` (Baris 61–63)
  - `app/Services/PublicUrlGeneratorService.php` (Baris 36–43)
- **Tingkat Keparahan:** **MEDIUM**
- **Akar Masalah:**
  Berbeda dengan Fonnte yang mendukung upload file multipart langsung (`fopen($qrLocalPath)`), Whacenter **hanya** menerima gambar via URL publik di parameter `'file' => $qrUrl`.
  Pada `PublicUrlGeneratorService::validateUrl()`, sistem melempar exception jika URL tidak menggunakan HTTPS atau domainnya adalah IP lokal / localhost:
  ```php
  if ($host === 'localhost' || $host === '127.0.0.1' || $host === '::1') {
      throw new \InvalidArgumentException("URL host cannot be local...");
  }
  ```
  Saat exception ditangkap, `$qrUrl` menjadi `null`. Whacenter kemudian hanya mengirim pesan teks tanpa ada gambar QR yang terlampir tanpa memunculkan notifikasi error ke staf.
- **Dampak Bisnis:**
  Tamu menerima pesan WhatsApp tanpa gambar QR voucher jika konfigurasi URL publik bermasalah atau saat pengujian staging.
- **Rekomendasi Solusi:**
  Tambahkan notifikasi peringatan (*warning log*) yang jelas di log pengiriman dan pertimbangkan integrasi cloud storage (S3/GCS) untuk URL file publik Whacenter.

---

### BUG-09: Celah Keamanan: Role Master `super-admin` Dapat Dihapus atau Diubah Namanya
- **File Terdampak:**
  - `app/Http/Controllers/RoleController.php` (Baris 71–75)
  - `app/Services/RoleService.php` (Baris 32–60)
- **Tingkat Keparahan:** **MEDIUM**
- **Akar Masalah:**
  Di `RoleController::destroy`, proteksi penghapusan hanya diterapkan pada nama `'admin'`:
  ```php
  if ($role->name === 'admin') {
      return redirect()->route('roles.index')->with('error', 'The admin role cannot be deleted.');
  }
  ```
  Padahal di seluruh codebase (seperti middleware, gate multi-properti, dan seeder), role tertinggi yang digunakan adalah **`super-admin`**.
  Jika pengguna dengan akses `roles.manage` menghapus atau mengganti nama role `super-admin`, seluruh bypass otorisasi dan akses dashboard admin akan terkunci atau rusak.
- **Rekomendasi Solusi:**
  Tambahkan proteksi permanen pada `RoleController` dan `RoleService` agar role `super-admin` dan `admin` tidak dapat dihapus maupun di-rename.

---

### BUG-10: Kebocoran Data Antar Properti (*Cross-Property Data Leak*) pada Laporan & Log
- **File Terdampak:**
  - `app/Http/Controllers/QrScanLogController.php` (Baris 16–74, 76–95)
  - `app/Http/Controllers/DeliveryLogController.php` (Baris 16–43)
  - `app/Http/Controllers/ReportController.php` (Baris 40–58)
- **Tingkat Keparahan:** **MEDIUM**
- **Akar Masalah:**
  Pada modul Booking dan Voucher, sistem telah menerapkan proteksi `authorizePropertyAccess()` dan pembatasan berdasarkan properti pengguna (`auth()->user()->properties`).
  Namun pada modul:
  - `QrScanLogController` (Riwayat Scan QR)
  - `DeliveryLogController` (Riwayat Pesan WhatsApp)
  - `ReportController` (Statistik dan Laporan Ringkasan)
  Query database tidak menyaring data berdasarkan properti yang ditugaskan ke staf.
- **Dampak Bisnis:**
  Staf cabang Properti A dapat melihat dan mengekspor data tamu, nomor telepon, dan histori scan milik Properti B.
- **Rekomendasi Solusi:**
  Terapkan filter `whereIn('property_id', $userPropertyIds)` pada ketiga controller tersebut bagi user non-`super-admin`.

---

### BUG-11: Inkonsistensi Logika Masa Berlaku Penambahan Pax (*Add-on Pax*)
- **File Terdampak:**
  - `app/Models/GuestVoucher.php` (Baris 82–100 vs Baris 294)
  - `app/Services/VoucherService.php` (Baris 403 vs Baris 564)
  - `app/Http/Controllers/GuestRedemptionReportController.php` (Baris 70)
- **Tingkat Keparahan:** **MEDIUM**
- **Akar Masalah:**
  Terdapat kontradiksi aturan penambahan kuota ekstra pax (*Addition Pax*):
  1. Pada `additionAppliesOn()` (`GuestVoucher.php`) dan `GuestRedemptionReportController`, penambahan pax dianggap hanya berlaku pada tanggal penambahan tersebut diberikan (`$dateString === $this->addition_date`).
  2. Pada `GuestVoucher::getFacilityStatuses()` dan `VoucherService::redeem()` untuk voucher reguler, penambahan pax diizinkan bergulir terus menerus ke hari-hari berikutnya (`$this->addition_date <= $dateString`).
- **Dampak Bisnis:**
  Tampilan jumlah tamu pada kartu voucher dan laporan penukaran berbeda dengan sisa kuota yang bisa ditebus di outlet. Jika tamu membeli 1 sarapan tambahan untuk hari pertama, sistem voucher reguler membiarkan tambahan tersebut dipakai lagi di hari kedua dan ketiga.
- **Rekomendasi Solusi:**
  Klarifikasi aturan bisnis: apakah penambahan pax bersifat harian (*one-day boost*) atau berlaku sepanjang sisa masa menginap, kemudian samakan seluruh rumusnya di model, service, dan controller report.

---

### BUG-12: Full-Table In-Memory Collection Filtering pada Laporan Penukaran Tamu
- **File Terdampak:**
  - `app/Http/Controllers/GuestRedemptionReportController.php` (Baris 22–60)
- **Tingkat Keparahan:** **MEDIUM (Performa)**
- **Akar Masalah:**
  Controller mengeksekusi:
  ```php
  $vouchers = GuestVoucher::query()->with([...])->get()->filter(function ($voucher) use ($date) {
      if ($voucher->status !== \App\Enums\VoucherStatus::Active) return false;
      // ...
  });
  ```
  Pemanggilan `->get()` tanpa klausa `WHERE status = 'active'` menarik seluruh riwayat voucher di database resort sejak awal berdiri beserta relasinya ke dalam RAM PHP, lalu baru difilter menggunakan fungsi koleksi PHP.
- **Dampak Bisnis:**
  Seiring bertambahnya jumlah voucher (ribuan hingga puluhan ribu), halaman laporan penukaran akan mengalami error *PHP Fatal Error: Allowed memory size exhausted* dan loading menjadi sangat lambat.
- **Rekomendasi Solusi:**
  Pindahkan filter status dan rentang tanggal langsung ke dalam SQL query database (`where('status', VoucherStatus::Active)`) sebelum memanggil `get()` atau `paginate()`.

---

## 6. Temuan Bug Rendah & Peningkatan Operasional (Low Severity)

### BUG-13: Duplikasi Data Tamu (`guests`) pada Import PMS Tanpa Email
- **File Terdampak:** `app/Imports/BookingsImport.php` (Baris 61–75)
- **Akar Masalah:**
  Saat import reservasi, pencarian tamu yang sudah ada hanya didasarkan pada kolom `guest_email`. Jika kolom email kosong (sering terjadi pada booking OTA walk-in), sistem langsung memanggil `Guest::create()`.
- **Dampak:**
  Tamu yang sama dibuatkan baris baru berulang kali di tabel `guests` setiap kali ada reservasi baru.
- **Rekomendasi:**
  Cari tamu berdasarkan kombinasi nomor telepon atau nama lengkap jika email tidak disediakan.

---

### BUG-14: Nomor Baris Spreadsheet Tidak Tercatat pada Log Kegagalan Import (`Row: N/A`)
- **File Terdampak:** `app/Imports/BookingsImport.php` (Baris 95, 119, 129)
- **Akar Masalah:**
  Saat validasi custom gagal (misal format tanggal salah atau properti tidak ditemukan), array failure menyematkan `'row' => 'N/A'` secara statis.
- **Dampak:**
  Staf yang melihat laporan import gagal tidak tahu baris Excel mana yang salah input.
- **Rekomendasi:**
  Gunakan counter baris riil spreadsheet agar staf dapat langsung menuju baris Excel yang bermasalah.

---

### BUG-15: Ketiadaan Hak Akses Granular untuk Edit dan Hapus Booking
- **File Terdampak:**
  - `app/Http/Controllers/BookingController.php` (Baris 313, 329)
  - `database/seeders/RolesAndPermissionsSeeder.php`
- **Akar Masalah:**
  Metode `update` dan `destroy` pada `BookingController` menggunakan pemeriksaan `$this->authorizePermission('bookings.create')`.
- **Dampak:**
  Staf pembuat reservasi otomatis memiliki hak untuk mengubah tanggal atau menghapus data reservasi tanpa supervisi.
- **Rekomendasi:**
  Buat permission khusus `bookings.edit` dan `bookings.delete` untuk kontrol wewenang staf yang lebih ketat.

---

## 7. Rekomendasi Roadmap & Prioritas Perbaikan

Berdasarkan tingkat dampak terhadap operasional harian resort, berikut rekomendasi urutan penanganan:

### Tahap 1: Prioritas Utama (Mendesak & Wajib Segera Diberlakukan)
1. **BUG-01:** Perbaikan formula `isOneTimeGracePeriodActive` agar scan sarapan tamu di pagi hari checkout tidak lagi terblokir. *(Sudah siap & diverifikasi di branch lokal)*
2. **BUG-02:** Koreksi mapping status pada `BookingsImport` agar reservasi PMS confirmed tetap berstatus `expected_arrival` dan voucher tidak terbit prematur. *(Sudah siap & diverifikasi di branch lokal)*
3. **BUG-03:** Penyelarasan jam kedaluwarsa voucher pada `DailyMaintenance` dari jam 12:30 menjadi 21:00 WIB.

### Tahap 2: Prioritas Kedua (Integritas Data & Stabilitas Sistem)
4. **BUG-04:** Penambahan validasi tumpang tindih kamar (*room double booking*) pada import file PMS.
5. **BUG-05:** Penanganan pengecekan unik dengan `withTrashed()` pada reference booking agar tidak memicu error SQL 1062.
6. **BUG-06:** Pemisahan panggilan API WhatsApp dari dalam transaksi database untuk mencegah lock timeout.
7. **BUG-07:** Penyesuaian `countryCode` pada Fonnte untuk nomor tamu mancanegara.

### Tahap 3: Prioritas Ketiga (Keamanan, Multi-Property & Performa)
8. **BUG-09:** Penguncian role `super-admin` agar kebal dari aksi hapus/ubah nama.
9. **BUG-10:** Penerapan pembatasan properti (*property scoping*) pada log scan, log delivery, dan ringkasan laporan.
10. **BUG-11:** Penyelarasan aturan bisnis kuota tambahan tamu (*Add-on Pax*).
11. **BUG-12:** Optimasi query database pada laporan penukaran tamu untuk mencegah memory leak.
12. **BUG-13, BUG-14, BUG-15:** Penyempurnaan UX nomor baris import, pencegahan duplikasi guest, dan pemisahan permission edit/delete.

---
*Dokumen ini dibuat untuk ditinjau oleh User sebelum eksekusi commit dan deployment.*
