@extends('layouts.app')
@section('title', 'Dokumentasi Sistem')
@section('page_title', 'Dokumentasi Sistem')
@section('content')

<div class="row">
    <div class="col-lg-3 mb-4 docs-sidebar">
        <div class="card sticky-top" style="top: 1rem;">
            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="fas fa-list me-2"></i>Daftar Isi</h6>
                <button class="btn btn-sm btn-outline-light d-lg-none" type="button" data-bs-toggle="collapse" data-bs-target="#docsTocCollapse" aria-expanded="false">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
            <div class="collapse collapse-lg-show" id="docsTocCollapse">
            <div class="card-body p-2">
                <nav class="nav flex-column nav-pills">
                    <a class="nav-link py-1 px-2 small" href="#dashboard"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</a>
                    <a class="nav-link py-1 px-2 small" href="#properties"><i class="fas fa-hotel me-2"></i>Data Properti</a>
                    <a class="nav-link py-1 px-2 small" href="#rooms"><i class="fas fa-door-open me-2"></i>Data Kamar</a>
                    <a class="nav-link py-1 px-2 small" href="#guests"><i class="fas fa-users me-2"></i>Data Tamu</a>
                    <a class="nav-link py-1 px-2 small" href="#bookings"><i class="fas fa-calendar-check me-2"></i>Data Reservasi</a>
                    <a class="nav-link py-1 px-2 small" href="#facilities"><i class="fas fa-concierge-bell me-2"></i>Fasilitas</a>
                    <a class="nav-link py-1 px-2 small" href="#outlets"><i class="fas fa-store me-2"></i>Outlet</a>
                    <a class="nav-link py-1 px-2 small" href="#vouchers"><i class="fas fa-qrcode me-2"></i>Voucher</a>
                    <a class="nav-link py-1 px-2 small" href="#scan"><i class="fas fa-camera me-2"></i>Scan QR Code</a>
                    <a class="nav-link py-1 px-2 small" href="#redeem"><i class="fas fa-check-circle me-2"></i>Redeem Manual</a>
                    <a class="nav-link py-1 px-2 small" href="#reports"><i class="fas fa-chart-bar me-2"></i>Laporan</a>
                    <a class="nav-link py-1 px-2 small" href="#delivery-logs"><i class="fas fa-paper-plane me-2"></i>Log Pengiriman</a>
                    <a class="nav-link py-1 px-2 small" href="#scan-history"><i class="fas fa-history me-2"></i>Riwayat Scan</a>
                    <a class="nav-link py-1 px-2 small" href="#delivery-settings"><i class="fas fa-cog me-2"></i>Pengaturan Kirim</a>
                    <a class="nav-link py-1 px-2 small" href="#users"><i class="fas fa-user-cog me-2"></i>Manajemen Pengguna</a>
                    <a class="nav-link py-1 px-2 small" href="#public-card"><i class="fas fa-external-link-alt me-2"></i>Kartu Tamu Publik</a>
                </nav>
            </div>
            </div>
        </div>
    </div>

    <div class="col-lg-9">
        <div class="card mb-4">
            <div class="card-body">
                <h5><i class="fas fa-info-circle text-primary me-2"></i>Tentang Sistem Ini</h5>
                <p>Sistem ini adalah aplikasi manajemen voucher QR untuk resor yang menangani seluruh siklus hidup tamu, mulai dari data properti, kamar, reservasi, hingga penerbitan dan redeem voucher berbasis QR Code. Setiap voucher tamu dikaitkan dengan fasilitas tertentu dan memiliki kuota pemakaian harian yang dapat dipantau secara real-time.</p>
                <p class="mb-0">Dokumentasi ini menjelaskan fungsi setiap halaman dan panduan penggunaannya.</p>
            </div>
        </div>

        <div id="dashboard" class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</h5>
            </div>
            <div class="card-body">
                <p>Halaman utama yang menampilkan ringkasan data operasional resor secara real-time.</p>
                <h6 class="fw-bold mt-3"><i class="fas fa-chart-line text-primary me-1"></i>Informasi yang Ditampilkan</h6>
                <div class="table-responsive"><table class="table table-sm table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th>Metrik</th>
                            <th>Fungsi</th>
                        </tr>
                    </thead>
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
                    </tbody>
                </table></div>
            </div>
        </div>

        <div id="properties" class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-hotel me-2"></i>Data Properti</h5>
            </div>
            <div class="card-body">
                <p>Halaman ini digunakan untuk mengelola data properti resor.</p>
                <h6 class="fw-bold mt-3"><i class="fas fa-list text-primary me-1"></i>Relasi Data</h6>
                <p>Setiap properti memiliki kamar, tipe kamar, area/wilayah, fasilitas, outlet, tamu, dan reservasi yang terkait.</p>

                <h6 class="fw-bold mt-3"><i class="fas fa-cogs text-primary me-1"></i>Fitur CRUD</h6>
                <div class="table-responsive"><table class="table table-sm table-bordered">
                    <thead class="table-light">
                        <tr><th>Aksi</th><th>Deskripsi</th></tr>
                    </thead>
                    <tbody>
                        <tr><td><span class="badge bg-success">Tambah</span></td><td>Klik tombol "Add Property" lalu isi nama, kode unik, zona waktu, alamat, dan status aktif.</td></tr>
                        <tr><td><span class="badge bg-warning text-dark">Edit</span></td><td>Klik ikon pensil pada baris properti yang ingin diubah.</td></tr>
                        <tr><td><span class="badge bg-danger">Hapus</span></td><td>Klik ikon tong sampah. Data properti yang masih memiliki relasi tidak dapat dihapus (foreign key constraint).</td></tr>
                    </tbody>
                </table></div>
            </div>
        </div>

        <div id="rooms" class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-door-open me-2"></i>Data Kamar</h5>
            </div>
            <div class="card-body">
                <p>Mengelola data kamar di setiap properti.</p>
                <h6 class="fw-bold mt-3"><i class="fas fa-cogs text-primary me-1"></i>Fitur</h6>
                <div class="table-responsive"><table class="table table-sm table-bordered">
                    <thead class="table-light">
                        <tr><th>Fitur</th><th>Deskripsi</th></tr>
                    </thead>
                    <tbody>
                        <tr><td>CRUD Manual</td><td>Tambah, edit, dan hapus kamar satu per satu. Isian meliputi properti, area, tipe kamar, nomor kamar, kapasitas, status, tipe tempat tidur, dan lain-lain.</td></tr>
                        <tr><td>Import Excel/CSV</td><td>Klik "Import Rooms" pada halaman daftar kamar. Unduh template terlebih dahulu, isi data, lalu unggah. Sistem akan memproses secara batch.</td></tr>
                        <tr><td>Unduh Template</td><td>Klik "Download Template" untuk mendapatkan format file import yang benar.</td></tr>
                    </tbody>
                </table></div>
            </div>
        </div>

        <div id="guests" class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-users me-2"></i>Data Tamu</h5>
            </div>
            <div class="card-body">
                <p>Mengelola data tamu yang menginap di resor.</p>
                <h6 class="fw-bold mt-3"><i class="fas fa-cogs text-primary me-1"></i>Fitur</h6>
                <div class="table-responsive"><table class="table table-sm table-bordered">
                    <thead class="table-light">
                        <tr><th>Fitur</th><th>Deskripsi</th></tr>
                    </thead>
                    <tbody>
                        <tr><td>CRUD Manual</td><td>Tambah, edit, dan hapus data tamu. Data meliputi nama depan, nama belakang, email, nomor telepon, nomor WhatsApp, dan nomor identitas.</td></tr>
                        <tr><td>Import Excel/CSV</td><td>Sama seperti kamar, kamu dapat mengimport data tamu secara massal menggunakan template yang sudah disediakan.</td></tr>
                    </tbody>
                </table></div>
                <div class="alert alert-info mt-2 py-2 small">
                    <i class="fas fa-info-circle me-1"></i>Nomor WhatsApp digunakan untuk mengirimkan voucher QR secara otomatis.
                </div>
            </div>
        </div>

        <div id="bookings" class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-calendar-check me-2"></i>Data Reservasi</h5>
            </div>
            <div class="card-body">
                <p>Mengelola reservasi tamu. Reservasi menghubungkan tamu dengan kamar dan properti tertentu.</p>
                <h6 class="fw-bold mt-3"><i class="fas fa-cogs text-primary me-1"></i>Fitur</h6>
                <div class="table-responsive"><table class="table table-sm table-bordered">
                    <thead class="table-light">
                        <tr><th>Fitur</th><th>Deskripsi</th></tr>
                    </thead>
                    <tbody>
                        <tr><td>CRUD Manual</td><td>Tambah, edit, lihat detail, dan hapus reservasi. Isian meliputi properti, tamu, kamar, kode booking, referensi, tanggal check-in/out, jumlah dewasa/anak, dan lain-lain.</td></tr>
                        <tr><td>Check-In</td><td>Klik tombol "Check In" pada baris reservasi. Sistem akan mengaktifkan reservasi dan secara otomatis membuat fasilitas bawaan (default facilities) serta menghasilkan voucher tamu.</td></tr>
                        <tr><td>Check-Out</td><td>Klik tombol "Check Out" untuk mengakhiri masa menginap. Voucher tamu tidak lagi dapat digunakan setelah check-out.</td></tr>
                        <tr><td>Import Excel/CSV</td><td>Import reservasi secara massal. Template tersedia untuk diunduh.</td></tr>
                        <tr><td>Kirim Ulang WhatsApp</td><td>Dari halaman detail reservasi atau detail voucher, kirim ulang voucher QR ke nomor WhatsApp tamu.</td></tr>
                    </tbody>
                </table></div>

                <h6 class="fw-bold mt-3"><i class="fas fa-info-circle text-primary me-1"></i>Alur Check-In</h6>
                <ol>
                    <li>Admin memilih reservasi dan klik "Check In".</li>
                    <li>Sistem mengubah status reservasi menjadi Checked In.</li>
                    <li>Sistem membuat data <code>booking_facilities</code> berdasarkan fasilitas default properti.</li>
                    <li>Sistem menghasilkan voucher QR unik untuk tamu tersebut.</li>
                    <li>Voucher dapat dikirim ke WhatsApp tamu (jika fitur pengiriman aktif).</li>
                </ol>

                <div class="alert alert-warning mt-2 py-2 small">
                    <i class="fas fa-exclamation-triangle me-1"></i>Reservasi harus dalam status Checked In agar voucher dapat digunakan untuk redeem fasilitas.
                </div>
            </div>
        </div>

        <div id="facilities" class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-concierge-bell me-2"></i>Fasilitas</h5>
            </div>
            <div class="card-body">
                <p>Mengelola template fasilitas yang tersedia di setiap properti. Fasilitas adalah layanan yang dapat di-redeem oleh tamu menggunakan voucher.</p>
                <h6 class="fw-bold mt-3"><i class="fas fa-cogs text-primary me-1"></i>Fitur CRUD</h6>
                <div class="table-responsive"><table class="table table-sm table-bordered">
                    <thead class="table-light">
                        <tr><th>Aksi</th><th>Deskripsi</th></tr>
                    </thead>
                    <tbody>
                        <tr><td><span class="badge bg-success">Tambah</span></td><td>Klik "Add Facility". Pilih properti, masukkan nama, kode unik, urutan tampilan (sort order), deskripsi, dan status aktif.</td></tr>
                        <tr><td><span class="badge bg-warning text-dark">Edit</span></td><td>Ubah data fasilitas yang sudah ada.</td></tr>
                        <tr><td><span class="badge bg-danger">Hapus</span></td><td>Hapus fasilitas. Tidak akan menghapus data reservasi yang sudah menggunakan fasilitas ini.</td></tr>
                    </tbody>
                </table></div>

                <h6 class="fw-bold mt-3"><i class="fas fa-tag text-primary me-1"></i>Tipe Fasilitas</h6>
                <p>Berdasarkan kode fasilitas, sistem mengenali dua tipe:</p>
                <div class="table-responsive"><table class="table table-sm table-bordered">
                    <thead class="table-light">
                        <tr><th>Tipe</th><th>Kode</th><th>Perilaku</th></tr>
                    </thead>
                    <tbody>
                        <tr><td>Harian</td><td>Semua kode selain SNACK, JOURNAL, FEED</td><td>Kuota direset setiap hari. Tersedia selama rentang tanggal yang ditentukan di booking facility.</td></tr>
                        <tr><td>Sekali Pakai</td><td>SNACK, JOURNAL, FEED</td><td>Hanya tersedia pada tanggal check-in dan hanya dapat digunakan satu kali selama masa menginap.</td></tr>
                    </tbody>
                </table></div>
            </div>
        </div>

        <div id="outlets" class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-store me-2"></i>Outlet</h5>
            </div>
            <div class="card-body">
                <p>Mengelola outlet atau lokasi fisik di mana tamu dapat melakukan redeem voucher. Setiap outlet terhubung ke satu properti dan satu fasilitas.</p>
                <h6 class="fw-bold mt-3"><i class="fas fa-cogs text-primary me-1"></i>Fitur CRUD</h6>
                <div class="table-responsive"><table class="table table-sm table-bordered">
                    <thead class="table-light">
                        <tr><th>Aksi</th><th>Deskripsi</th></tr>
                    </thead>
                    <tbody>
                        <tr><td><span class="badge bg-success">Tambah</span></td><td>Pilih properti, pilih fasilitas yang dilayani outlet ini, masukkan nama outlet, kode unik, dan status aktif.</td></tr>
                        <tr><td><span class="badge bg-warning text-dark">Edit</span></td><td>Ubah data outlet, termasuk mengganti fasilitas yang dilayani.</td></tr>
                        <tr><td><span class="badge bg-danger">Hapus</span></td><td>Hapus outlet. Data redeem yang sudah tercatat tetap tersimpan.</td></tr>
                    </tbody>
                </table></div>

                <div class="alert alert-info mt-2 py-2 small">
                    <i class="fas fa-info-circle me-1"></i>Outlet sangat penting dalam proses redeem. Setiap transaksi redeem akan mencatat outlet mana yang melayani, sehingga laporan dapat dirinci per outlet.
                </div>
            </div>
        </div>

        <div id="vouchers" class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-qrcode me-2"></i>Voucher</h5>
            </div>
            <div class="card-body">
                <p>Halaman ini menampilkan semua voucher tamu dan menyediakan fitur pembuatan voucher sementara (temporary).</p>
                <h6 class="fw-bold mt-3"><i class="fas fa-list text-primary me-1"></i>Daftar Voucher</h6>
                <p>Tabel menampilkan: nama tamu, kamar, tanggal menginap / kedaluwarsa, kode QR, secure token, status, dan tombol aksi.</p>
                <p>Filter pencarian: teks (berdasarkan kode QR, nama tamu, kode booking), status (Active / Redeemed / Expired), kategori (Standard / Temporary), properti, dan rentang tanggal.</p>

                <h6 class="fw-bold mt-3"><i class="fas fa-eye text-primary me-1"></i>Lihat Detail Voucher</h6>
                <p>Klik tombol <i class="fas fa-qrcode"></i> untuk melihat kartu tamu digital yang menampilkan QR Code, informasi tamu, dan tombol untuk melihat kartu publik atau mengirim ulang via WhatsApp.</p>

                <h6 class="fw-bold mt-3"><i class="fas fa-edit text-primary me-1"></i>Edit Voucher</h6>
                <p>Klik tombol <i class="fas fa-edit"></i> untuk mengubah fasilitas yang terhubung dengan voucher dan batas pax. Kamu dapat:</p>
                <ul>
                    <li>Menambah fasilitas dengan mencentang kotak fasilitas yang diinginkan.</li>
                    <li>Menghapus fasilitas dengan menghilangkan centang.</li>
                    <li>Mengubah jumlah pax limit (maksimum orang per redeem).</li>
                </ul>

                <h6 class="fw-bold mt-3"><i class="fas fa-plus-circle text-primary me-1"></i>Buat Voucher Sementara (Temporary)</h6>
                <p>Voucher sementara digunakan untuk tamu yang tidak memiliki reservasi di sistem (misalnya tamu walk-in atau pengunjung harian).</p>
                <ol>
                    <li>Klik tombol expand pada panel "Generate Temporary QR Voucher".</li>
                    <li>Pilih properti.</li>
                    <li>Masukkan nama tamu dan batas pax.</li>
                    <li>Tentukan tipe kedaluwarsa: "Hours from now" (berapa jam dari sekarang) atau "Specific date" (tanggal tertentu).</li>
                    <li>Pilih satu atau lebih fasilitas yang dapat diakses. Centang "Select All Facilities" untuk memilih semua fasilitas aktif di properti tersebut.</li>
                    <li>Klik "Generate Temporary Voucher".</li>
                </ol>

                <div class="alert alert-info mt-2 py-2 small">
                    <i class="fas fa-info-circle me-1"></i>Voucher standar (standard) dihasilkan secara otomatis saat check-in reservasi. Voucher sementara (temporary) dibuat manual melalui halaman ini.
                </div>

                <h6 class="fw-bold mt-3"><i class="fas fa-sync-alt text-primary me-1"></i>Status Voucher</h6>
                <div class="table-responsive"><table class="table table-sm table-bordered">
                    <thead class="table-light">
                        <tr><th>Status</th><th>Fungsi</th></tr>
                    </thead>
                    <tbody>
                        <tr><td><span class="badge bg-success">Active</span></td><td>Voucher aktif dan dapat digunakan untuk redeem.</td></tr>
                        <tr><td><span class="badge bg-secondary">Redeemed</span></td><td>Semua fasilitas pada voucher sudah digunakan habis.</td></tr>
                        <tr><td><span class="badge bg-danger">Expired</span></td><td>Voucher sudah kedaluwarsa (lewat jam 21:00 pada tanggal check-out untuk standar, atau lewat expires_at untuk temporary).</td></tr>
                    </tbody>
                </table></div>
            </div>
        </div>

        <div id="scan" class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-camera me-2"></i>Scan QR Code</h5>
            </div>
            <div class="card-body">
                <p>Halaman ini digunakan oleh staf outlet untuk memproses redeem voucher menggunakan kamera perangkat.</p>
                <h6 class="fw-bold mt-3"><i class="fas fa-steps text-primary me-1"></i>Langkah-langkah</h6>
                <ol>
                    <li><strong>Pilih Outet:</strong> Pilih outlet lokasi staf bertugas. Outlet dikelompokkan berdasarkan properti.</li>
                    <li><strong>Scan atau Masukkan Kode QR:</strong>
                        <ul>
                            <li>Klik "Start Camera" untuk mengaktifkan kamera. Arahkan kamera ke QR Code tamu. Sistem akan otomatis mendeteksi dan memverifikasi.</li>
                            <li>Klik "Switch Camera" untuk berganti kamera depan/belakang.</li>
                            <li>Atau masukkan secure token atau kode QR secara manual pada kolom teks, lalu klik "Verify".</li>
                        </ul>
                    </li>
                    <li><strong>Verifikasi:</strong> Sistem akan menampilkan data tamu, kamar, kode booking, dan daftar fasilitas yang tersedia beserta kuota hariannya.</li>
                    <li><strong>Pilih Fasilitas:</strong> Klik kartu fasilitas yang ingin di-redeem.</li>
                    <li><strong>Masukkan Jumlah Pax:</strong> Tentukan jumlah orang yang akan menggunakan fasilitas (tidak boleh melebihi sisa kuota).</li>
                    <li><strong>Konfirmasi:</strong> Klik "Confirm Redemption". Sistem akan mencatat transaksi dan menampilkan hasilnya.</li>
                </ol>

                <h6 class="fw-bold mt-3"><i class="fas fa-shield-alt text-primary me-1"></i>Keamanan</h6>
                <ul>
                    <li>QR Code yang tidak valid akan ditolak.</li>
                    <li>Outlet hanya bisa redeem voucher untuk properti yang sama.</li>
                    <li>Voucher yang sudah kedaluwarsa atau sudah fully redeemed tidak dapat digunakan.</li>
                    <li>Sistem memiliki rate limiting untuk mencegah penyalahgunaan.</li>
                    <li>Setiap percobaan scan dicatat dalam riwayat scan.</li>
                </ul>
            </div>
        </div>

        <div id="redeem" class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-check-circle me-2"></i>Redeem Manual</h5>
            </div>
            <div class="card-body">
                <p>Alternatif untuk staf yang tidak memiliki kamera. Fungsinya sama dengan Scan QR Code, tetapi input dilakukan secara manual.</p>
                <ol>
                    <li>Pilih outlet lokasi.</li>
                    <li>Masukkan secure token atau teks QR Code pada kolom input.</li>
                    <li>Klik "Verify Voucher".</li>
                    <li>Lanjutkan dengan memilih fasilitas dan memasukkan jumlah pax seperti pada halaman scan.</li>
                </ol>
            </div>
        </div>

        <div id="reports" class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Laporan</h5>
            </div>
            <div class="card-body">
                <p>Halaman laporan komprehensif untuk menganalisis data redeem voucher.</p>
                <h6 class="fw-bold mt-3"><i class="fas fa-filter text-primary me-1"></i>Filter</h6>
                <div class="table-responsive"><table class="table table-sm table-bordered">
                    <thead class="table-light">
                        <tr><th>Filter</th><th>Fungsi</th></tr>
                    </thead>
                    <tbody>
                        <tr><td>Periode</td><td>Harian, bulanan, atau rentang tanggal kustom.</td></tr>
                        <tr><td>Properti</td><td>Filter berdasarkan properti tertentu.</td></tr>
                        <tr><td>Fasilitas</td><td>Filter berdasarkan fasilitas tertentu.</td></tr>
                        <tr><td>Outlet</td><td>Filter berdasarkan outlet tertentu.</td></tr>
                    </tbody>
                </table></div>

                <h6 class="fw-bold mt-3"><i class="fas fa-chart-pie text-primary me-1"></i>Data yang Ditampilkan</h6>
                <ul>
                    <li>Ringkasan statistik: total redeem, total pax, rata-rata harian.</li>
                    <li>Rincian redeem per outlet.</li>
                    <li>Rincian redeem per fasilitas.</li>
                    <li>Tren harian dalam bentuk grafik atau tabel.</li>
                    <li>Riwayat redeem terbaru.</li>
                </ul>

                <h6 class="fw-bold mt-3"><i class="fas fa-download text-primary me-1"></i>Ekspor</h6>
                <p>Klik "Export" untuk mengunduh laporan dalam format XLSX, XLS, atau CSV. Data yang diekspor mengikuti filter yang sedang aktif.</p>

                <div class="alert alert-info mt-2 py-2 small">
                    <i class="fas fa-info-circle me-1"></i>Fitur ekspor memerlukan permission <code>reports.export</code>.
                </div>
            </div>
        </div>

        <div id="delivery-logs" class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-paper-plane me-2"></i>Log Pengiriman</h5>
            </div>
            <div class="card-body">
                <p>Mencatat semua pengiriman voucher WhatsApp yang telah dilakukan sistem.</p>
                <h6 class="fw-bold mt-3"><i class="fas fa-list text-primary me-1"></i>Informasi yang Ditampilkan</h6>
                <ul>
                    <li>Nama tamu dan nomor telepon tujuan.</li>
                    <li>Status pengiriman: Terkirim, Gagal, Tertunda.</li>
                    <li>Waktu pengiriman dan respon dari provider.</li>
                    <li>Tombol untuk mengekspor log ke Excel/CSV.</li>
                </ul>
            </div>
        </div>

        <div id="scan-history" class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-history me-2"></i>Riwayat Scan</h5>
            </div>
            <div class="card-body">
                <p>Mencatat setiap percobaan scan QR Code, baik yang berhasil maupun gagal. Berguna untuk audit keamanan dan troubleshooting.</p>
                <h6 class="fw-bold mt-3"><i class="fas fa-filter text-primary me-1"></i>Filter</h6>
                <ul>
                    <li>Hasil scan: success, not_found, invalid_outlet, quota_exceeded, dll.</li>
                    <li>Outlet tempat scan dilakukan.</li>
                    <li>Rentang tanggal.</li>
                </ul>

                <h6 class="fw-bold mt-3"><i class="fas fa-download text-primary me-1"></i>Ekspor</h6>
                <p>Riwayat scan dapat diekspor ke XLSX, XLS, atau CSV.</p>
            </div>
        </div>

        <div id="delivery-settings" class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-cog me-2"></i>Pengaturan Pengiriman</h5>
            </div>
            <div class="card-body">
                <p>Mengatur konfigurasi pengiriman voucher QR melalui WhatsApp.</p>
                <h6 class="fw-bold mt-3"><i class="fas fa-sliders-h text-primary me-1"></i>Pengaturan yang Tersedia</h6>
                <div class="table-responsive"><table class="table table-sm table-bordered">
                    <thead class="table-light">
                        <tr><th>Pengaturan</th><th>Fungsi</th></tr>
                    </thead>
                    <tbody>
                        <tr><td>Fonnte Token</td><td>Token API dari layanan Fonnte untuk mengirim pesan WhatsApp.</td></tr>
                        <tr><td>Template Pesan</td><td>Template teks pesan yang akan dikirim. Tersedia variable: <code>{guest_name}</code>, <code>{voucher_url}</code>, <code>{property_name}</code>.</td></tr>
                        <tr><td>Aktifkan Pengiriman</td><td>Toggle untuk mengaktifkan atau menonaktifkan pengiriman WhatsApp otomatis.</td></tr>
                    </tbody>
                </table></div>
            </div>
        </div>

        <div id="users" class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-user-cog me-2"></i>Manajemen Pengguna</h5>
            </div>
            <div class="card-body">
                <p>Mengelola pengguna sistem dan peran (role) mereka.</p>

                <h6 class="fw-bold mt-3"><i class="fas fa-users-cog text-primary me-1"></i>Pengguna</h6>
                <p>CRUD pengguna: tambah, edit, aktifkan/nonaktifkan, dan hapus pengguna. Setiap pengguna dapat diberikan satu atau lebih peran.</p>

                <h6 class="fw-bold mt-3"><i class="fas fa-tags text-primary me-1"></i>Peran (Roles) & Hak Akses</h6>
                <p>Sistem menggunakan permission-based access control. Berikut daftar permission yang tersedia:</p>
                <div class="row">
                    <div class="col-md-6">
                        <div class="table-responsive"><table class="table table-sm table-bordered">
                            <thead class="table-light">
                                <tr><th>Permission</th><th>Fungsi</th></tr>
                            </thead>
                            <tbody>
                                <tr><td><code>properties.manage</code></td><td>Kelola properti</td></tr>
                                <tr><td><code>rooms.manage</code></td><td>Kelola kamar</td></tr>
                                <tr><td><code>guests.manage</code></td><td>Kelola tamu</td></tr>
                                <tr><td><code>bookings.view</code></td><td>Lihat reservasi</td></tr>
                                <tr><td><code>bookings.create</code></td><td>Buat/edit/hapus reservasi, check-in/out</td></tr>
                                <tr><td><code>bookings.checkin</code></td><td>Check-in tamu</td></tr>
                                <tr><td><code>bookings.checkout</code></td><td>Check-out tamu</td></tr>
                                <tr><td><code>vouchers.view</code></td><td>Lihat daftar dan detail voucher</td></tr>
                                <tr><td><code>vouchers.generate</code></td><td>Generate dan edit voucher</td></tr>
                                <tr><td><code>vouchers.redeem</code></td><td>Akses halaman scan dan redeem</td></tr>
                                <tr><td><code>vouchers.resend</code></td><td>Kirim ulang voucher WhatsApp</td></tr>
                                <tr><td><code>facilities.manage</code></td><td>Kelola fasilitas dan outlet</td></tr>
                                <tr><td><code>reports.view</code></td><td>Lihat laporan dan riwayat scan</td></tr>
                                <tr><td><code>reports.export</code></td><td>Ekspor laporan</td></tr>
                                <tr><td><code>delivery_settings.manage</code></td><td>Kelola pengiriman WhatsApp</td></tr>
                                <tr><td><code>delivery_logs.view</code></td><td>Lihat log pengiriman</td></tr>
                                <tr><td><code>users.manage</code></td><td>Kelola pengguna</td></tr>
                                <tr><td><code>roles.manage</code></td><td>Kelola peran</td></tr>
                            </tbody>
                        </table></div>
                    </div>
                </div>

                <h6 class="fw-bold mt-3"><i class="fas fa-user-shield text-primary me-1"></i>Peran Bawaan</h6>
                <div class="table-responsive"><table class="table table-sm table-bordered">
                    <thead class="table-light">
                        <tr><th>Role</th><th>Fungsi</th></tr>
                    </thead>
                    <tbody>
                        <tr><td><code>super-admin</code></td><td>Akses penuh ke semua fitur. Peran ini dilindungi dan tidak dapat dihapus.</td></tr>
                        <tr><td><code>admin</code></td><td>Sebagian besar permission manajemen, kecuali beberapa permission khusus.</td></tr>
                    </tbody>
                </table></div>
            </div>
        </div>

        <div id="public-card" class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-external-link-alt me-2"></i>Kartu Tamu Publik</h5>
            </div>
            <div class="card-body">
                <p>Halaman publik yang dapat diakses tanpa login, menampilkan kartu tamu digital berisi QR Code dan status fasilitas.</p>
                <h6 class="fw-bold mt-3"><i class="fas fa-link text-primary me-1"></i>Akses</h6>
                <p>URL: <code>{{ url('/v/{secureToken}') }}</code> — secure token adalah token unik 32 karakter yang terenkripsi dalam QR Code.</p>
                <h6 class="fw-bold mt-3"><i class="fas fa-info-circle text-primary me-1"></i>Informasi yang Ditampilkan</h6>
                <ul>
                    <li>Nama tamu</li>
                    <li>Kode dan nama kamar</li>
                    <li>Tanggal check-in dan check-out</li>
                    <li>QR Code yang dapat di-scan oleh staf outlet</li>
                    <li>Status pemakaian fasilitas (progress bar) untuk setiap fasilitas yang terdaftar</li>
                </ul>
                <p>QR Code yang ditampilkan di halaman ini berisi secure token yang sama, sehingga staf dapat memindainya untuk proses redeem.</p>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    .nav-pills .nav-link {
        color: #495057;
        border-radius: 0.25rem;
    }
    .nav-pills .nav-link:hover {
        background-color: #e9ecef;
    }
    .card-header h5 {
        font-size: 1.1rem;
        font-weight: 600;
    }
    .sticky-top {
        z-index: 101;
    }
    h6.fw-bold {
        font-size: 0.95rem;
    }
</style>
@endpush
@endsection
