@extends('layouts.app')
@section('title', 'Dokumentasi Sistem')
@section('page_title', 'Dokumentasi Sistem')
@section('content')
<div class="row">
    <div class="col-lg-3 mb-4">
        <div class="card card-primary card-outline sticky-top" style="top:57px;">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-list me-2"></i>Daftar Isi</h3>
                <div class="card-tools d-lg-none">
                    <button class="btn btn-tool" type="button" data-bs-toggle="collapse" data-bs-target="#docsToc">
                        <i class="fas fa-bars"></i>
                    </button>
                </div>
            </div>
            <div class="card-body p-2 collapse collapse-lg-show" id="docsToc">
                <nav class="nav flex-column nav-pills">
                    <a class="nav-link py-1 px-2 small" href="#dashboard"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</a>
                    <a class="nav-link py-1 px-2 small" href="#properties"><i class="fas fa-hotel me-2"></i>Properti</a>
                    <a class="nav-link py-1 px-2 small" href="#rooms"><i class="fas fa-door-open me-2"></i>Kamar</a>
                    <a class="nav-link py-1 px-2 small" href="#guests"><i class="fas fa-users me-2"></i>Tamu</a>
                    <a class="nav-link py-1 px-2 small" href="#bookings"><i class="fas fa-calendar-check me-2"></i>Reservasi</a>
                    <a class="nav-link py-1 px-2 small" href="#facilities"><i class="fas fa-concierge-bell me-2"></i>Fasilitas</a>
                    <a class="nav-link py-1 px-2 small" href="#outlets"><i class="fas fa-store me-2"></i>Outlet</a>
                    <a class="nav-link py-1 px-2 small" href="#vouchers"><i class="fas fa-qrcode me-2"></i>Voucher</a>
                    <a class="nav-link py-1 px-2 small" href="#scan"><i class="fas fa-camera me-2"></i>Scan QR</a>
                    <a class="nav-link py-1 px-2 small" href="#redeem"><i class="fas fa-check-circle me-2"></i>Redeem Manual</a>
                    <a class="nav-link py-1 px-2 small" href="#reports"><i class="fas fa-chart-bar me-2"></i>Laporan</a>
                    <a class="nav-link py-1 px-2 small" href="#delivery-logs"><i class="fas fa-paper-plane me-2"></i>Log Kirim</a>
                    <a class="nav-link py-1 px-2 small" href="#scan-history"><i class="fas fa-history me-2"></i>Riwayat Scan</a>
                    <a class="nav-link py-1 px-2 small" href="#delivery-settings"><i class="fas fa-cog me-2"></i>Pengaturan Kirim</a>
                    <a class="nav-link py-1 px-2 small" href="#users"><i class="fas fa-user-cog me-2"></i>Pengguna</a>
                    <a class="nav-link py-1 px-2 small" href="#public-card"><i class="fas fa-external-link-alt me-2"></i>Kartu Publik</a>
                </nav>
            </div>
        </div>
    </div>

    <div class="col-lg-9">
        <div id="dashboard" class="card card-primary card-outline">
            <div class="card-header"><h3 class="card-title"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</h3></div>
            <div class="card-body">
                <p>Halaman utama yang menampilkan ringkasan data operasional resor secara real-time.</p>
                <h6 class="fw-bold mt-3"><i class="fas fa-chart-line text-primary me-1"></i>Informasi yang Ditampilkan</h6>
                <div class="table-responsive"><table class="table table-sm table-bordered"><thead class="table-light"><tr><th>Metrik</th><th>Fungsi</th></tr></thead>
                <tbody>
                    <tr><td>Total Tamu</td><td>Jumlah seluruh tamu yang terdaftar dalam sistem.</td></tr>
                    <tr><td>Tamu Aktif</td><td>Tamu yang sedang dalam masa menginap (status Checked In).</td></tr>
                    <tr><td>Total Reservasi</td><td>Jumlah seluruh reservasi yang tercatat.</td></tr>
                    <tr><td>Kuota Harian</td><td>Total kuota pemakaian fasilitas untuk hari ini.</td></tr>
                    <tr><td>Sudah Redeem</td><td>Jumlah pemakaian kuota yang sudah dilakukan hari ini.</td></tr>
                    <tr><td>Sisa Kuota</td><td>Selisih kuota harian yang masih tersedia.</td></tr>
                    <tr><td>Fasilitas Terpopuler</td><td>Daftar fasilitas yang paling sering digunakan.</td></tr>
                    <tr><td>Aktivitas Outlet</td><td>Ringkasan transaksi redeem per outlet.</td></tr>
                    <tr><td>Statistik Pengiriman</td><td>Jumlah voucher terkirim, gagal, tertunda, dan tingkat keberhasilan pengiriman WhatsApp.</td></tr>
                </tbody></table></div>
                <h6 class="fw-bold mt-3"><i class="fas fa-star text-primary me-1"></i>Fitur Utama</h6>
                <ul>
                    <li>Statistik real-time dengan update otomatis setiap halaman dimuat.</li>
                    <li>Link cepat ke halaman terkait (Guests, Bookings, Reports, Delivery Logs).</li>
                    <li>Tabel aktivitas redeem terbaru hari ini dengan informasi tamu, fasilitas, pax, outlet, dan staf.</li>
                    <li>Daftar fasilitas terpopuler berdasarkan total pemakaian.</li>
                </ul>
            </div>
        </div>

        <div id="properties" class="card card-primary card-outline">
            <div class="card-header"><h3 class="card-title"><i class="fas fa-hotel me-2"></i>Data Properti</h3></div>
            <div class="card-body">
                <p>Mengelola data properti resor. Setiap properti menjadi induk bagi kamar, area, fasilitas, outlet, tamu, dan reservasi.</p>
                <h6 class="fw-bold mt-3"><i class="fas fa-cogs text-primary me-1"></i>Operasi CRUD</h6>
                <div class="table-responsive"><table class="table table-sm table-bordered"><thead class="table-light"><tr><th style="width:100px;">Aksi</th><th>Deskripsi</th></tr></thead>
                <tbody>
                    <tr><td><span class="badge bg-success">Create</span></td><td>Klik tombol <strong>+ Add Property</strong>. Isi nama, kode unik, zona waktu, alamat, dan status aktif.</td></tr>
                    <tr><td><span class="badge bg-warning text-dark">Update</span></td><td>Klik ikon <i class="fas fa-edit text-warning"></i> pada baris properti. Ubah data yang diperlukan lalu simpan.</td></tr>
                    <tr><td><span class="badge bg-danger">Delete</span></td><td>Klik ikon <i class="fas fa-trash text-danger"></i>. Properti yang memiliki relasi tidak dapat dihapus (foreign key constraint).</td></tr>
                </tbody></table></div>
            </div>
        </div>

        <div id="rooms" class="card card-primary card-outline">
            <div class="card-header"><h3 class="card-title"><i class="fas fa-door-open me-2"></i>Data Kamar</h3></div>
            <div class="card-body">
                <p>Mengelola data kamar di setiap properti.</p>
                <h6 class="fw-bold mt-3"><i class="fas fa-cogs text-primary me-1"></i>Fitur</h6>
                <div class="table-responsive"><table class="table table-sm table-bordered"><thead class="table-light"><tr><th style="width:140px;">Fitur</th><th>Deskripsi</th></tr></thead>
                <tbody>
                    <tr><td>CRUD Manual</td><td>Tambah, edit, dan hapus kamar satu per satu. Isian: properti, area, tipe kamar, nomor kamar, kapasitas, status, tipe tempat tidur, lokasi.</td></tr>
                    <tr><td>Import Excel/CSV</td><td>Klik <strong>Import</strong>, unduh template, isi data massal, lalu unggah. Duplikat nomor kamar dalam satu properti dilewati.</td></tr>
                </tbody></table></div>
                <h6 class="fw-bold mt-3"><i class="fas fa-cogs text-primary me-1"></i>Operasi CRUD</h6>
                <div class="table-responsive"><table class="table table-sm table-bordered"><thead class="table-light"><tr><th style="width:100px;">Aksi</th><th>Deskripsi</th></tr></thead>
                <tbody>
                    <tr><td><span class="badge bg-success">Create</span></td><td>Klik <strong>+ Add Room</strong>. Pilih properti, isi nomor kamar, tipe, kapasitas, tipe tempat tidur, area/lokasi, dan status.</td></tr>
                    <tr><td><span class="badge bg-warning text-dark">Update</span></td><td>Klik ikon <i class="fas fa-edit text-warning"></i>. Ubah data kamar seperti tipe, kapasitas, atau status.</td></tr>
                    <tr><td><span class="badge bg-danger">Delete</span></td><td>Klik ikon <i class="fas fa-trash text-danger"></i>. Hapus kamar yang sudah tidak digunakan.</td></tr>
                </tbody></table></div>
            </div>
        </div>

        <div id="guests" class="card card-primary card-outline">
            <div class="card-header"><h3 class="card-title"><i class="fas fa-users me-2"></i>Data Tamu</h3></div>
            <div class="card-body">
                <p>Mengelola data tamu yang menginap di resor.</p>
                <h6 class="fw-bold mt-3"><i class="fas fa-cogs text-primary me-1"></i>Fitur</h6>
                <ul>
                    <li>CRUD Manual — tambah, edit, lihat detail, dan hapus data tamu.</li>
                    <li>Import Excel/CSV — import massal menggunakan template. Duplikat email/telepon dilewati.</li>
                </ul>
                <div class="callout callout-info py-2 small"><i class="fas fa-info-circle me-1"></i>Nomor WhatsApp digunakan untuk mengirimkan voucher QR secara otomatis.</div>
                <h6 class="fw-bold mt-3"><i class="fas fa-cogs text-primary me-1"></i>Operasi CRUD</h6>
                <div class="table-responsive"><table class="table table-sm table-bordered"><thead class="table-light"><tr><th style="width:100px;">Aksi</th><th>Deskripsi</th></tr></thead>
                <tbody>
                    <tr><td><span class="badge bg-success">Create</span></td><td>Klik <strong>+ Add Guest</strong>. Isi nama depan/belakang, email, nomor telepon, WhatsApp, dan nomor identitas.</td></tr>
                    <tr><td><span class="badge bg-warning text-dark">Update</span></td><td>Klik ikon <i class="fas fa-edit text-warning"></i> untuk mengubah data tamu.</td></tr>
                    <tr><td><span class="badge bg-danger">Delete</span></td><td>Klik ikon <i class="fas fa-trash text-danger"></i> untuk menghapus data tamu.</td></tr>
                </tbody></table></div>
            </div>
        </div>

        <div id="bookings" class="card card-primary card-outline">
            <div class="card-header"><h3 class="card-title"><i class="fas fa-calendar-check me-2"></i>Data Reservasi</h3></div>
            <div class="card-body">
                <p>Mengelola reservasi tamu. Reservasi menghubungkan tamu dengan kamar — titik awal alur kerja voucher.</p>
                <h6 class="fw-bold mt-3"><i class="fas fa-cogs text-primary me-1"></i>Fitur</h6>
                <div class="table-responsive"><table class="table table-sm table-bordered"><thead class="table-light"><tr><th style="width:160px;">Fitur</th><th>Deskripsi</th></tr></thead>
                <tbody>
                    <tr><td>CRUD Manual</td><td>Tambah, edit, lihat detail, hapus. Isian: properti, tamu, kamar, kode booking, tanggal in/out, jumlah dewasa/anak, fasilitas.</td></tr>
                    <tr><td>Check-In</td><td>Klik <strong>Check In</strong>. Sistem mengaktifkan reservasi, membuat fasilitas bawaan properti, dan menghasilkan voucher QR.</td></tr>
                    <tr><td>Check-Out</td><td>Klik <strong>Check Out</strong>. Voucher tamu tidak lagi dapat digunakan.</td></tr>
                    <tr><td>Import Excel/CSV</td><td>Import reservasi massal. Template tersedia. Duplikat referensi dilewati.</td></tr>
                </tbody></table></div>
                <h6 class="fw-bold mt-3"><i class="fas fa-info-circle text-primary me-1"></i>Alur Check-In</h6>
                <ol>
                    <li>Admin memilih reservasi dan klik <strong>Check In</strong>.</li>
                    <li>Sistem mengubah status reservasi menjadi <span class="badge bg-success">Checked In</span>.</li>
                    <li>Sistem membuat data <code>booking_facilities</code> berdasarkan fasilitas default properti.</li>
                    <li>Sistem menghasilkan voucher QR unik untuk tamu tersebut.</li>
                    <li>Voucher dapat dikirim ke WhatsApp tamu (jika fitur pengiriman aktif).</li>
                </ol>
                <div class="callout callout-warning py-2 small"><i class="fas fa-exclamation-triangle me-1"></i>Reservasi harus berstatus <strong>Checked In</strong> agar voucher dapat digunakan untuk redeem fasilitas.</div>
            </div>
        </div>

        <div id="facilities" class="card card-primary card-outline">
            <div class="card-header"><h3 class="card-title"><i class="fas fa-concierge-bell me-2"></i>Fasilitas</h3></div>
            <div class="card-body">
                <p>Template fasilitas yang dapat di-redeem menggunakan voucher.</p>
                <h6 class="fw-bold mt-3"><i class="fas fa-tag text-primary me-1"></i>Tipe Fasilitas</h6>
                <div class="table-responsive"><table class="table table-sm table-bordered"><thead class="table-light"><tr><th>Tipe</th><th>Kode</th><th>Perilaku</th></tr></thead>
                <tbody>
                    <tr><td><span class="badge bg-primary">Harian</span></td><td>Semua selain SNACK, JOURNAL, FEED</td><td>Kuota direset tiap hari. Tersedia selama rentang tanggal.</td></tr>
                    <tr><td><span class="badge bg-info">Sekali Pakai</span></td><td>SNACK, JOURNAL, FEED</td><td>Hanya pada tanggal check-in, satu kali selama menginap.</td></tr>
                </tbody></table></div>
                <h6 class="fw-bold mt-3"><i class="fas fa-cogs text-primary me-1"></i>Operasi CRUD</h6>
                <div class="table-responsive"><table class="table table-sm table-bordered"><thead class="table-light"><tr><th style="width:100px;">Aksi</th><th>Deskripsi</th></tr></thead>
                <tbody>
                    <tr><td><span class="badge bg-success">Create</span></td><td>Klik <strong>+ Add Facility</strong>. Pilih properti, masukkan nama, kode unik, urutan tampilan, deskripsi, status.</td></tr>
                    <tr><td><span class="badge bg-warning text-dark">Update</span></td><td>Ubah data fasilitas, termasuk nama, kode, atau urutan tampilan.</td></tr>
                    <tr><td><span class="badge bg-danger">Delete</span></td><td>Hapus fasilitas. Data reservasi yang sudah menggunakan fasilitas ini tetap tersimpan.</td></tr>
                </tbody></table></div>
            </div>
        </div>

        <div id="outlets" class="card card-primary card-outline">
            <div class="card-header"><h3 class="card-title"><i class="fas fa-store me-2"></i>Outlet</h3></div>
            <div class="card-body">
                <p>Lokasi fisik tempat tamu melakukan redeem voucher. Satu outlet dapat melayani satu atau lebih fasilitas.</p>
                <div class="callout callout-info py-2 small"><i class="fas fa-info-circle me-1"></i>Setiap transaksi redeem mencatat outlet yang melayani, sehingga laporan dapat dirinci per outlet.</div>
                <h6 class="fw-bold mt-3"><i class="fas fa-cogs text-primary me-1"></i>Operasi CRUD</h6>
                <div class="table-responsive"><table class="table table-sm table-bordered"><thead class="table-light"><tr><th style="width:100px;">Aksi</th><th>Deskripsi</th></tr></thead>
                <tbody>
                    <tr><td><span class="badge bg-success">Create</span></td><td>Klik <strong>+ Add Outlet</strong>. Pilih properti, pilih satu/lebih fasilitas, masukkan nama outlet, kode unik, status.</td></tr>
                    <tr><td><span class="badge bg-warning text-dark">Update</span></td><td>Ubah data outlet, termasuk mengganti atau menambah fasilitas.</td></tr>
                    <tr><td><span class="badge bg-danger">Delete</span></td><td>Hapus outlet. Data redeem yang sudah tercatat tetap tersimpan.</td></tr>
                </tbody></table></div>
            </div>
        </div>

        <div id="vouchers" class="card card-primary card-outline">
            <div class="card-header"><h3 class="card-title"><i class="fas fa-qrcode me-2"></i>Voucher</h3></div>
            <div class="card-body">
                <p>Daftar semua voucher tamu, fitur pembuatan voucher sementara, dan pengeditan fasilitas voucher.</p>
                <h6 class="fw-bold mt-3"><i class="fas fa-list text-primary me-1"></i>Daftar &amp; Filter</h6>
                <p>Tabel: nama tamu, kamar, tanggal, QR code, secure token, status, aksi. Filter: teks, status (Active/Redeemed/Expired), kategori (Standard/Temporary), properti, tanggal.</p>
                <h6 class="fw-bold mt-3"><i class="fas fa-eye text-primary me-1"></i>Lihat Detail</h6>
                <p>Klik <i class="fas fa-qrcode"></i> untuk melihat kartu tamu digital — QR code, info tamu, tombol kartu publik &amp; kirim ulang WhatsApp.</p>
                <h6 class="fw-bold mt-3"><i class="fas fa-edit text-primary me-1"></i>Edit Voucher</h6>
                <p>Klik <i class="fas fa-edit"></i> untuk mengubah fasilitas dan batas pax pada voucher.</p>
                <h6 class="fw-bold mt-3"><i class="fas fa-plus-circle text-primary me-1"></i>Voucher Sementara</h6>
                <p>Untuk tamu tanpa reservasi (walk-in).</p>
                <ol>
                    <li>Expand panel <strong>Generate Temporary QR Voucher</strong>.</li>
                    <li>Pilih properti, isi nama tamu &amp; batas pax, tentukan kedaluwarsa.</li>
                    <li>Pilih satu/lebih fasilitas, klik <strong>Generate</strong>.</li>
                </ol>
                <h6 class="fw-bold mt-3"><i class="fas fa-sync-alt text-primary me-1"></i>Status Voucher</h6>
                <div class="table-responsive"><table class="table table-sm table-bordered"><thead class="table-light"><tr><th style="width:100px;">Status</th><th>Fungsi</th></tr></thead>
                <tbody>
                    <tr><td><span class="badge bg-success">Active</span></td><td>Voucher aktif dan dapat digunakan untuk redeem.</td></tr>
                    <tr><td><span class="badge bg-secondary">Redeemed</span></td><td>Semua fasilitas sudah digunakan habis.</td></tr>
                    <tr><td><span class="badge bg-danger">Expired</span></td><td>Voucher sudah kedaluwarsa.</td></tr>
                </tbody></table></div>
            </div>
        </div>

        <div id="scan" class="card card-primary card-outline">
            <div class="card-header"><h3 class="card-title"><i class="fas fa-camera me-2"></i>Scan QR Code</h3></div>
            <div class="card-body">
                <p>Staf outlet memproses redeem voucher menggunakan kamera perangkat.</p>
                <h6 class="fw-bold mt-3"><i class="fas fa-steps text-primary me-1"></i>Langkah-langkah</h6>
                <ol>
                    <li><strong>Pilih Outlet</strong> — lokasi staf bertugas (dikelompokkan per properti).</li>
                    <li><strong>Scan atau Masukkan Kode QR</strong> — klik <strong>Start Camera</strong> atau masukkan secure token manual, lalu <strong>Verify</strong>.</li>
                    <li><strong>Verifikasi</strong> — sistem menampilkan data tamu, kamar, fasilitas, dan kuota harian.</li>
                    <li><strong>Pilih Fasilitas &amp; Pax</strong> — klik kartu fasilitas, tentukan jumlah orang.</li>
                    <li><strong>Konfirmasi</strong> — klik <strong>Confirm Redemption</strong>.</li>
                </ol>
                <h6 class="fw-bold mt-3"><i class="fas fa-shield-alt text-primary me-1"></i>Keamanan</h6>
                <ul>
                    <li>QR Code tidak valid ditolak. Outlet hanya bisa redeem properti yang sama.</li>
                    <li>Voucher expired/fully redeemed tidak dapat digunakan. Rate limiting &amp; riwayat scan.</li>
                </ul>
            </div>
        </div>

        <div id="redeem" class="card card-primary card-outline">
            <div class="card-header"><h3 class="card-title"><i class="fas fa-check-circle me-2"></i>Redeem Manual</h3></div>
            <div class="card-body">
                <p>Alternatif untuk staf tanpa kamera. Fungsinya sama dengan Scan QR Code.</p>
                <ol>
                    <li>Pilih outlet lokasi.</li>
                    <li>Masukkan secure token atau teks QR Code.</li>
                    <li>Klik <strong>Verify Voucher</strong>.</li>
                    <li>Pilih fasilitas &amp; pax, klik <strong>Confirm Redemption</strong>.</li>
                </ol>
            </div>
        </div>

        <div id="reports" class="card card-primary card-outline">
            <div class="card-header"><h3 class="card-title"><i class="fas fa-chart-bar me-2"></i>Laporan</h3></div>
            <div class="card-body">
                <p>Analisis data redeem voucher secara komprehensif.</p>
                <h6 class="fw-bold mt-3"><i class="fas fa-filter text-primary me-1"></i>Filter</h6>
                <ul><li>Periode (harian/bulanan/kustom), properti, fasilitas, outlet.</li></ul>
                <h6 class="fw-bold mt-3"><i class="fas fa-chart-pie text-primary me-1"></i>Data</h6>
                <ul>
                    <li>Ringkasan: total redeem, total pax, tamu unik, rata-rata per redeem.</li>
                    <li>Rincian per fasilitas (progress bar), per outlet, tren harian, riwayat 15 redeem terakhir.</li>
                </ul>
                <h6 class="fw-bold mt-3"><i class="fas fa-download text-primary me-1"></i>Ekspor</h6>
                <p>Klik <strong>Export</strong> → XLSX, XLS, CSV. Data mengikuti filter aktif. Perlu permission <code>reports.export</code>.</p>
            </div>
        </div>

        <div id="delivery-logs" class="card card-primary card-outline">
            <div class="card-header"><h3 class="card-title"><i class="fas fa-paper-plane me-2"></i>Log Pengiriman</h3></div>
            <div class="card-body">
                <p>Riwayat pengiriman voucher WhatsApp.</p>
                <ul>
                    <li>Nama tamu, nomor tujuan, status (<span class="badge bg-success">Terkirim</span> / <span class="badge bg-danger">Gagal</span> / <span class="badge bg-warning text-dark">Tertunda</span>).</li>
                    <li>Waktu pengiriman, respons provider, konten pesan. Ekspor ke Excel/CSV.</li>
                </ul>
            </div>
        </div>

        <div id="scan-history" class="card card-primary card-outline">
            <div class="card-header"><h3 class="card-title"><i class="fas fa-history me-2"></i>Riwayat Scan</h3></div>
            <div class="card-body">
                <p>Audit setiap percobaan scan QR Code (berhasil/gagal).</p>
                <ul>
                    <li>Filter: hasil scan, outlet, tanggal.</li>
                    <li>Data: timestamp, kode QR, tamu, kamar, outlet, staf, hasil, IP. Ekspor ke Excel/CSV.</li>
                </ul>
            </div>
        </div>

        <div id="delivery-settings" class="card card-primary card-outline">
            <div class="card-header"><h3 class="card-title"><i class="fas fa-cog me-2"></i>Pengaturan Pengiriman</h3></div>
            <div class="card-body">
                <p>Konfigurasi pengiriman voucher QR melalui WhatsApp.</p>
                <div class="table-responsive"><table class="table table-sm table-bordered"><thead class="table-light"><tr><th style="width:220px;">Pengaturan</th><th>Fungsi</th></tr></thead>
                <tbody>
                    <tr><td>WhatsApp Delivery</td><td>Toggle aktif/nonaktifkan fitur pengiriman.</td></tr>
                    <tr><td>Delivery Method</td><td>QR Code Image Attachment atau Public Guest Card Link.</td></tr>
                    <tr><td>Auto/Scheduled</td><td>Kirim otomatis saat check-in atau terjadwal.</td></tr>
                    <tr><td>Fonnte Token</td><td>Token API untuk layanan WhatsApp.</td></tr>
                    <tr><td>Message Template</td><td>Template dengan variable: <code>{guest_name}</code>, <code>{voucher_link}</code>, dll.</td></tr>
                </tbody></table></div>
            </div>
        </div>

        <div id="users" class="card card-primary card-outline">
            <div class="card-header"><h3 class="card-title"><i class="fas fa-user-cog me-2"></i>Manajemen Pengguna</h3></div>
            <div class="card-body">
                <p>CRUD pengguna + manajemen role &amp; permission.</p>
                <h6 class="fw-bold mt-3"><i class="fas fa-cogs text-primary me-1"></i>Operasi</h6>
                <div class="table-responsive"><table class="table table-sm table-bordered"><thead class="table-light"><tr><th style="width:100px;">Aksi</th><th>Deskripsi</th></tr></thead>
                <tbody>
                    <tr><td><span class="badge bg-success">Create</span></td><td>Klik <strong>+ Add New User</strong>. Isi nama, email, password (min. 8 karakter), pilih status dan role.</td></tr>
                    <tr><td><span class="badge bg-warning text-dark">Update</span></td><td>Ubah data, reset password, ubah status atau role.</td></tr>
                    <tr><td><span class="badge bg-danger">Delete</span></td><td>Hapus pengguna. Tidak bisa menghapus akun sendiri.</td></tr>
                </tbody></table></div>

                <h6 class="fw-bold mt-3"><i class="fas fa-key text-primary me-1"></i>Permission</h6>
                <div class="row"><div class="col-md-6">
                    <div class="table-responsive"><table class="table table-sm table-bordered"><thead class="table-light"><tr><th>Permission</th><th>Fungsi</th></tr></thead>
                    <tbody>
                        <tr><td><code>properties.manage</code></td><td>Kelola properti</td></tr>
                        <tr><td><code>rooms.manage</code></td><td>Kelola kamar</td></tr>
                        <tr><td><code>guests.manage</code></td><td>Kelola tamu</td></tr>
                        <tr><td><code>bookings.view</code></td><td>Lihat reservasi</td></tr>
                        <tr><td><code>bookings.create</code></td><td>CRUD reservasi + check-in/out</td></tr>
                        <tr><td><code>vouchers.view</code></td><td>Lihat voucher</td></tr>
                        <tr><td><code>vouchers.generate</code></td><td>Generate voucher</td></tr>
                        <tr><td><code>vouchers.edit</code></td><td>Edit fasilitas voucher</td></tr>
                        <tr><td><code>vouchers.redeem</code></td><td>Akses scan &amp; redeem</td></tr>
                        <tr><td><code>vouchers.resend</code></td><td>Kirim ulang WhatsApp</td></tr>
                        <tr><td><code>facilities.manage</code></td><td>Kelola fasilitas &amp; outlet</td></tr>
                        <tr><td><code>reports.view</code></td><td>Lihat laporan</td></tr>
                        <tr><td><code>reports.export</code></td><td>Ekspor laporan</td></tr>
                        <tr><td><code>delivery_settings.manage</code></td><td>Kelola pengiriman</td></tr>
                        <tr><td><code>delivery_logs.view</code></td><td>Lihat log kirim</td></tr>
                        <tr><td><code>users.manage</code></td><td>Kelola pengguna</td></tr>
                        <tr><td><code>roles.manage</code></td><td>Kelola role</td></tr>
                    </tbody></table></div>
                </div></div>

                <h6 class="fw-bold mt-3"><i class="fas fa-user-shield text-primary me-1"></i>Role Bawaan</h6>
                <div class="table-responsive"><table class="table table-sm table-bordered"><thead class="table-light"><tr><th>Role</th><th>Fungsi</th></tr></thead>
                <tbody>
                    <tr><td><span class="badge bg-dark">super-admin</span></td><td>Akses penuh ke semua fitur. Dilindungi, tidak bisa dihapus.</td></tr>
                    <tr><td><span class="badge bg-primary">admin</span></td><td>Sebagian besar permission manajemen.</td></tr>
                    <tr><td><span class="badge bg-info">*-staff</span></td><td>Staff per fasilitas. Hanya lihat voucher &amp; redeem.</td></tr>
                </tbody></table></div>
            </div>
        </div>

        <div id="public-card" class="card card-primary card-outline">
            <div class="card-header"><h3 class="card-title"><i class="fas fa-external-link-alt me-2"></i>Kartu Tamu Publik</h3></div>
            <div class="card-body">
                <p>Halaman publik tanpa login — QR code + status fasilitas real-time.</p>
                <p>URL: <code>{{ url('/v/{secureToken}') }}</code> (token unik 32 karakter dalam QR Code).</p>
                <ul>
                    <li>Nama tamu, kode kamar, tanggal check-in/out.</li>
                    <li>QR Code untuk scan staf outlet.</li>
                    <li>Progress bar sisa kuota per fasilitas.</li>
                </ul>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    .nav-pills .nav-link { color:#495057; border-radius:0.25rem; }
    .nav-pills .nav-link:hover { background:#e9ecef; }
    .sticky-top { z-index:101; }
    .callout { border-radius:0.25rem; box-shadow:none; }
    .callout-info { background:#e3f2fd; border-left:3px solid #2196f3; }
    .callout-warning { background:#fff8e1; border-left:3px solid #ff9800; }
    .card-header h3 { font-size:1.1rem; font-weight:600; }
    .card-body { font-size:0.92rem; }
    .card-body p { color:#475569; }
    @media(max-width:991px) { .sticky-top { position:static !important; } }
</style>
@endpush
@endsection
