 # ERD
 ![ERD ini mengintegrasikan entitas User, Alat, Peminjaman, dan Pengembalian untuk mengelola siklus pemakaian barang secara sistematis. Melalui relasi antar tabel, sistem memastikan keterkaitan data yang akurat mulai dari identitas peminjam hingga status pengembalian alat. Selain itu, hadirnya log aktivitas berfungsi mencatat setiap tindakan pengguna, sehingga seluruh riwayat transaksi terdokumentasi dengan baik untuk kebutuhan audit dan keamanan.](assets/dokumentasi/ERD/ERD.png)

# Web Office Track

Web Office Track merupakan platform berbasis website yang dirancang untuk meningkatkan efisiensi dan meminimalkan human error bagi staf dan pengelola barang di kantor. Dibangun menggunakan PHP, HTML, CSS, dan database MySQL, aplikasi ini mengoptimalkan pengelolaan siklus hidup peminjaman melalui struktur database relasional yang sistematis. Dengan penerapan Foreign Key dan Transaction Management, Office Track menjamin integritas data di setiap tahapan, mulai dari pengajuan hingga pengembalian, guna memastikan akurasi stok dan konsistensi status transaksi secara real-time serta mencegah terjadinya peminjaman ganda pada aset kantor.

# Daftar Fitur
 - Fitur Laporan Perkembangan: Memudahkan pemantauan data secara real-time yang disusun berdasarkan timeline tertentu untuk melihat tren  penggunaan sistem.
 - Sistem Denda Otomatis: Fitur khusus untuk menangani denda bagi peminjam yang melakukan perusakan barang atau mengalami keterlambatan dalam pengembalian.
 - Dashboard Pengguna (User): Antarmuka yang didesain khusus agar peminjam dapat melakukan peminjaman, pengembalian, dan memantau riwayat aktivitas mereka dengan mudah.
 - Login & Otorisasi Berbasis Role: Akses masuk yang terkontrol ketat oleh admin, di mana setiap hak akses (otorisasi) disesuaikan sepenuhnya dengan peran yang telah ditetapkan.
 - Dashboard Admin & Petugas: Panel kendali utama yang didesain secara efisien untuk membantu pengelola dalam mengorganisir dan memvalidasi seluruh data yang masuk ke sistem.
 - Sirkulasi Barang yang Praktis: Fitur peminjaman dan pengembalian yang alurnya dibuat sesederhana mungkin untuk meningkatkan pengalaman pengguna.

# Dokumentasi Fungsi/Prosedur

## 1. check_login() - helpers.php


- tujuan: Memeriksa apakah user sudah memiliki sesi login yang aktif.
- parameter:
  - Tidak ada parameter.
- proses:
  1. Memeriksa apakah variabel sesi $_SESSION['user'] telah diatur.
  2. Jika tidak ada, mengalihkan pengguna ke halaman login menggunakan header location.
  3. Menghentikan eksekusi script dengan exit().
- output: Tidak ada return value, melakukan pengalihan halaman jika belum login.

## 2. check_role() - helpers.php
- tujuan Memeriksa apakah user memiliki peran (role) yang sesuai untuk mengakses halaman tertentu.
- parameter: 
  `$required_role` (string): Peran yang diperlukan (contoh: 'admin', 'petugas').
- proses:
  1. Memanggil fungsi check_login() untuk memastikan status login.
  2. Membandingkan role user di session dengan $required_role.
  3. Jika tidak cocok, mengirimkan kode respons HTTP 403 dan menampilkan template HTML "Akses Ditolak".
  4. Menghentikan eksekusi script.
- output: HTML error message dan terminasi script jika role tidak sesuai.

## 3. log_activity() - helpers.php
- tujuan: Mencatat setiap aktivitas yang dilakukan user ke dalam tabel log database.
- parameter:
  - `$user_id ` (int): ID unik user yang melakukan aksi.
  - `$aktivitas` (string): Deskripsi tindakan yang dilakukan.
- proses:
  1. Mengambil koneksi database melalui Database::getConnection().
  2. Menyiapkan query INSERT untuk tabel log_aktivitas.
  3. Memasukkan ID user, deskripsi aktivitas, dan waktu saat ini menggunakan fungsi SQL NOW().
- output: Boolean (true jika berhasil, false jika gagal).

## 4. sanitize_input() - helpers.php
- tujuan: Membersihkan data input dari karakter berbahaya untuk mencegah XSS dan spasi tidak perlu.
- parameter:
  - `$data` (string): Data mentah dari input form.
- proses:
  1. Menghilangkan spasi di awal dan akhir string dengan trim().
  2. Menghilangkan karakter backslash dengan stripslashes().
  3. Mengubah karakter khusus menjadi entitas HTML dengan htmlspecialchars().
- output: String yang sudah dibersihkan (sanitized).

## 5. format_tanggal() - helpers.php
- tujuan: Mengubah format tanggal standar database (Y-m-d) menjadi format tanggal bahasa Indonesia yang mudah dibaca.
- parameter:
  - `$tanggal` (string): String tanggal format YYYY-MM-DD.
- proses:
  1. Memeriksa apakah input kosong, jika ya kembalikan '-'.
  2. Memecah string tanggal berdasarkan karakter dash (-).
  3. Memetakan angka bulan (01-12) ke nama bulan bahasa Indonesia (Januari-Desember).
  4. Menyusun kembali menjadi format: Tanggal Nama-Bulan Tahun.
- output: String tanggal terformat (contoh: "25 Januari 2024").

## 6. getConnection() - database.php (Class Database)
- tujuan: Menyediakan satu instance koneksi database PDO yang konsisten di seluruh aplikasi (Singleton).
- parameter: Tidak ada parameter.
- proses:
  1. Memeriksa apakah properti statis $instance sudah terisi.
  2. Jika belum, membuat instance baru dari class Database.
  3. Mengembalikan objek PDO yang tersimpan dalam properti $pdo.
- output: Objek koneksi PDO.




## 7. buildApprovalFilterSql() - petugas/approval.php
- tujuan: Membangun query SQL untuk filtering data peminjaman yang perlu disetujui
- parameter:
  - `$conn` (mysqli): Koneksi database
- proses:
  1. Mengambil parameter GET (q, from, to)
  2. Membangun kondisi WHERE untuk status 'menunggu'
  3. Menambahkan filter search dan tanggal
  4. Menggabungkan dengan ORDER BY tanggal_pinjam DESC
- output: String query SQL lengkap untuk approval

## 8. buildAdminFilterSql() - admin/peminjaman.php
- tujuan: Membangun query SQL untuk filtering data peminjaman di admin panel
- parameter:
  - `$conn` (mysqli): Koneksi database
- proses:
  1. Mengambil parameter GET (q, from, to)
  2. Membangun kondisi WHERE untuk semua status peminjaman
  3. Menambahkan filter search dan tanggal
  4. Menggabungkan dengan ORDER BY tanggal_pinjam DESC
- output: String query SQL lengkap untuk admin peminjaman

## 9. buildFilterSql() - petugas/laporan.php
- tujuan: Membangun query SQL untuk filtering data laporan pengembalian
- parameter:
  - `$conn` (mysqli): Koneksi database
- proses:
  1. Mengambil parameter GET (q, from, to)
  2. Membangun kondisi WHERE untuk data pengembalian
  3. Menambahkan filter search dan tanggal
  4. Menggabungkan dengan ORDER BY tanggal_dikembalikan DESC
- output: String query SQL lengkap untuk laporan

## 10. generatePDF() - vendor/FPDF/fpdf.php
- tujuan: Menghasilkan file PDF dari data yang diberikan
- parameter:
  - `$data` (array): Array data yang akan diekspor ke PDF
- proses:
  1. Membuat instance FPDF
  2. Mengatur font dan layout halaman
  3. Menambahkan header tabel
  4. Loop melalui data dan menambahkannya ke tabel PDF
  5. Mengatur format dan styling
- output: File PDF dengan tabel data yang diformat

# Dokumentasi Operasi CRUD

## 1. Manajemen User - admin/users.php

### Create User (Tambah User)
- tujuan: Menambahkan user baru ke sistem dengan role tertentu
- parameter `$_POST`:
  - `nama`: Nama lengkap user.
  - `username`: Username unik untuk login.
  - `password`: Password user (akan di-hash menggunakan password_hash()).
  - `role`: Peran user (admin, petugas, atau peminjam).
- proses:
  1. Validasi semua input untuk memastikan tidak kosong dan formatnya benar.
  2. Amankan input menggunakan fungsi sanitize_input().
  3. Hash password sebelum disimpan ke database.
  4. Eksekusi query INSERT ke tabel users.
  5. Jika berhasil, panggil fungsi log_activity() untuk mencatat "Menambahkan user baru: [nama]".
  6. Set pesan sukses ke $_SESSION.
  7. Redirect kembali ke halaman admin/users.php.
- output: User baru tersimpan di tabel users, log aktivitas tercatat, dan halaman ter-refresh dengan pesan sukses.

### Read User (Tampil User)
- tujuan: Menampilkan daftar semua user yang terdaftar dalam sistem.
- parameter: Tidak ada (mengambil semua data).
- proses:
  1. Eksekusi query SELECT id, nama, username, role FROM users ORDER BY nama ASC.
  2. Lakukan perulangan (loop) untuk setiap baris hasil query.
  3. Tampilkan data dalam bentuk tabel HTML dengan kolom Nama, Username, Role, dan Aksi (Edit/Hapus).
- output: Tabel HTML yang menampilkan seluruh data user.

### Update User (Edit User)
- tujuan: Mengupdate data user yang sudah ada
- parameter `$_POST`:
  - `id_user`: ID user yang akan diupdate.
  - `nama`: Nama baru user.
  - `username`: Username baru user.
  - `password`: Password baru (kosongkan jika tidak ingin diubah).
  - `role`: Peran baru user.
- proses:
  1. Validasi dan amankan input.
  2. Siapkan query UPDATE untuk tabel users.
  3. Jika password tidak kosong, hash password baru dan sertakan dalam query UPDATE.
  4. Eksekusi query berdasarkan id_user.
  5. Panggil log_activity() untuk mencatat "Mengupdate data user: [nama]".
  6. Set pesan sukses dan redirect.
- output: Data user di tabel users berhasil diperbarui, log aktivitas tercatat.

### Delete User (Hapus User)
- tujuan:  Menghapus user dari sistem secara permanen.
- parameter `$_GET`:
  - `delete`: ID user yang akan dihapus.
- proses:
  1. Ambil id_user dari parameter $_GET['delete'].
  2. (Penting): Sebelum menghapus, ambil data user (misalnya nama) untuk keperluan log.
  3. Eksekusi query DELETE FROM users WHERE id = ?.
  4. Panggil log_activity() untuk mencatat "Menghapus user: [nama]".
- output: User terhapus dari database, redirect dengan pesan sukses

## 2. Alat Management CRUD - admin/alat.php

### Create Alat (Tambah Alat)
- tujuan: Menambahkan alat baru ke inventory
- parameter:
  - `$_POST['nama']`: Nama alat
  - `$_POST['id_kategori']`: ID kategori alat
  - `$_POST['stok']`: Jumlah stok tersedia
  - `$_POST['deskripsi']`: Deskripsi alat
  - `$_FILES['gambar']`: File gambar alat (opsional)
- proses:
  1. Validasi dan escape input
  2. Handle upload gambar jika ada
  3. Insert data ke tabel `alat`
  4. Set session success message
  5. Redirect ke halaman alat management
- output: Alat baru tersimpan dengan gambar (jika ada), redirect dengan pesan sukses

### Read Alat (Tampil Alat)
- tujuan: Menampilkan daftar semua alat dengan informasi lengkap
- parameter: Tidak ada (mengambil semua data)
- proses:
  1. Query SELECT dengan JOIN ke tabel `kategori`
  2. Loop melalui hasil untuk menampilkan dalam tabel
  3. Tampilkan nama, kategori, stok, gambar, deskripsi, dan action buttons
- output: Tabel HTML dengan data alat lengkap dan opsi edit/delete

### Update Alat (Edit Alat)
- tujuan: Mengupdate informasi alat yang sudah ada
- parameter:
  - `$_POST['id_alat']`: ID alat yang akan diupdate
  - `$_POST['nama']`: Nama baru
  - `$_POST['id_kategori']`: Kategori baru
  - `$_POST['stok']`: Stok baru
  - `$_POST['deskripsi']`: Deskripsi baru
  - `$_FILES['gambar']`: Gambar baru (opsional)
- proses:
  1. Validasi dan escape input
  2. Handle upload gambar baru jika ada
  3. Update record di tabel `alat`
  4. Set session success message
  5. Redirect ke halaman alat management
- output: Data alat terupdate, redirect dengan pesan sukses

### Delete Alat (Hapus Alat)
- tujuan: Menghapus alat dari inventory
- parameter:
  - `$_GET['delete']`: ID alat yang akan dihapus
- proses:
  1. Escape ID dari parameter GET
  2. Delete record dari tabel `alat`
  3. Set session success message
  4. Redirect ke halaman alat management
- output: Alat terhapus dari database, redirect dengan pesan sukses

## 3. Kategori Management CRUD - admin/kategori.php

### Create Kategori (Tambah Kategori)
- tujuan: Menambahkan kategori baru untuk mengelompokkan alat
- parameter:
  - `$_POST['nama']`: Nama kategori baru
- proses:
  1. Validasi dan escape input nama kategori
  2. Insert data ke tabel `kategori`
  3. Set session success message
  4. Redirect ke halaman kategori management
- output: Kategori baru tersimpan, redirect dengan pesan sukses

### Read Kategori (Tampil Kategori)
- tujuan: Menampilkan daftar semua kategori dengan jumlah alat di dalamnya
- parameter: Tidak ada (mengambil semua data)
- proses:
  1. Query SELECT semua kategori
  2. Untuk setiap kategori, hitung jumlah alat dengan query terpisah
  3. Tampilkan dalam grid layout dengan card untuk setiap kategori
- output: Grid cards kategori dengan jumlah alat di masing-masing kategori

### Update Kategori (Edit Kategori)
- tujuan: Mengubah nama kategori yang sudah ada
- parameter:
  - `$_POST['id_kategori']`: ID kategori yang akan diupdate
  - `$_POST['nama']`: Nama kategori baru
- proses:
  1. Validasi dan escape input
  2. Update nama kategori di tabel `kategori`
  3. Set session success message
  4. Redirect ke halaman kategori management
- output: Nama kategori terupdate, redirect dengan pesan sukses

### Delete Kategori (Hapus Kategori)
- tujuan: Menghapus kategori dari sistem
- parameter:
  - `$_GET['delete']`: ID kategori yang akan dihapus
- proses:
  1. Escape ID dari parameter GET
  2. Delete record dari tabel `kategori`
  3. Set session success message
  4. Redirect ke halaman kategori management
- output: Kategori terhapus, redirect dengan pesan sukses

## 4. Authentication CRUD - auth/login_process.php

### Create Session (Login)
- tujuan: Membuat session user setelah login berhasil
- parameter:
  - `$_POST['username']`: Username user
  - `$_POST['password']`: Password user
- proses:
  1. Validasi input username dan password
  2. Escape username untuk keamanan
  3. Query user dari database berdasarkan username
  4. Verifikasi password dengan password_verify() atau fallback plaintext
  5. Jika perlu rehash, update password ke hash baru
  6. Set session variables (login, id_user, nama, role)
  7. Insert log aktivitas login
  8. Redirect berdasarkan role user
- output: Session user aktif, redirect ke dashboard sesuai role

### Read Session (Cek Login Status)
- tujuan: Memverifikasi apakah user sudah login
- parameter: Tidak ada (menggunakan session)
- proses:
  1. Cek apakah $_SESSION['login'] ada dan bernilai true
  2. Jika tidak ada, redirect ke halaman login
  3. Jika ada, lanjutkan eksekusi halaman
- output: User boleh akses halaman atau redirect ke login

### Update Session (Role Check)
- tujuan: Memverifikasi role user untuk akses halaman tertentu
- parameter:
  - `$role`: Role yang diperlukan (string atau array)
- proses:
  1. Bandingkan $_SESSION['role'] dengan role yang diperlukan
  2. Jika tidak cocok, panggil fungsi aksesDitolak()
  3. Jika cocok, lanjutkan eksekusi
- output: Akses diberikan atau error message

### Delete Session (Logout)
- tujuan: Menghapus session user saat logout
- parameter: Tidak ada
- proses:
  1. Hapus semua session variables
  2. Destroy session
  3. Redirect ke halaman login
- output: User logged out, redirect ke login page

## 5. Peminjaman CRUD - peminjam/ajukan.php & petugas/approval.php

### Create Peminjaman (Ajukan Peminjaman)
- tujuan: Membuat pengajuan peminjaman alat baru
- parameter:
  - `$_POST['alat']`: ID alat yang dipinjam
  - `$_POST['jumlah']`: Jumlah alat yang dipinjam
  - `$_POST['tgl_pinjam']`: Tanggal mulai peminjaman
  - `$_POST['tgl_kembali']`: Tanggal pengembalian
  - `$_POST['confirm_peminjaman']`: Konfirmasi user
- proses:
  1. Validasi tanggal (kembali >= pinjam)
  2. Cek stok alat tersedia
  3. Validasi konfirmasi user
  4. Insert ke tabel `peminjaman` dengan status 'menunggu'
  5. Insert log aktivitas
  6. Set success message dan redirect
- output: Pengajuan peminjaman tersimpan, redirect dengan pesan sukses

### Read Peminjaman (Tampil Pengajuan)
- tujuan: Menampilkan daftar pengajuan peminjaman untuk approval
- parameter: Filter opsional (q, from, to)
- proses:
  1. Query peminjaman dengan status 'menunggu'
  2. JOIN dengan tabel user dan alat
  3. Terapkan filter jika ada
  4. Tampilkan dalam tabel dengan action approve/reject
- output: Tabel pengajuan peminjaman yang perlu disetujui

### Update Peminjaman (Approval)
- tujuan: Menyetujui atau menolak pengajuan peminjaman
- parameter:
  - `$_GET['approve']` atau `$_GET['reject']`: ID peminjaman
- proses:
  1. Update status peminjaman menjadi 'disetujui' atau 'ditolak'
  2. Jika disetujui, kurangi stok alat
  3. Insert log aktivitas
  4. Set success message dan redirect
- output: Status peminjaman terupdate, stok alat berkurang jika disetujui

## 6. Pengembalian CRUD - petugas/confirm_return.php & petugas/monitoring.php

### Create Pengembalian (Proses Pengembalian)
- tujuan: Mencatat pengembalian alat dan menghitung denda
- parameter:
  - `$_POST['denda_kerusakan']`: Denda kerusakan alat
  - `$_POST['pembayaran_diterima']`: Status pembayaran diterima
- proses:
  1. Hitung denda keterlambatan berdasarkan tanggal
  2. Update tabel `pengembalian` dengan denda dan status
  3. Kembalikan stok alat ke inventory
  4. Update status peminjaman menjadi 'selesai'
  5. Insert log aktivitas
  6. Set success message dan redirect
- output: Pengembalian tercatat, stok kembali, denda dihitung

### Read Pengembalian (Monitoring)
- tujuan: Menampilkan daftar pengembalian yang perlu dikonfirmasi
- parameter: Filter opsional (q, from, to)
- proses:
  1. Query pengembalian yang belum dikonfirmasi
  2. JOIN dengan tabel peminjaman, user, alat
  3. Hitung denda keterlambatan otomatis
  4. Tampilkan dalam tabel dengan action konfirmasi
- output: Tabel pengembalian yang perlu dikonfirmasi petugas

### Update Pengembalian (Konfirmasi)
- tujuan: Mengkonfirmasi pengembalian dan menetapkan denda final
- parameter:
  - `$_POST['confirm']`: Trigger konfirmasi
  - `$_POST['denda_kerusakan']`: Denda kerusakan final
  - `$_POST['pembayaran_diterima']`: Status pembayaran
- proses:
  1. Hitung ulang denda keterlambatan
  2. Update record pengembalian dengan denda final
  3. Update status pembayaran jika ada
  4. Kembalikan stok alat
  5. Update status peminjaman
  6. Insert log aktivitas
- output: Pengembalian dikonfirmasi, semua perhitungan final


# Debuging
![Ada sebuah bug dimana ketika kita mengajukan barang dan dikonfirmasi dan kita mengembalikan nya di hari yang sama maka kita kena denda terlambat 1 hari , kesalahan nya ada dilogic konfirmasi pengembalian barang dimana 1 hari yang sama dianggap sudah terlambat](assets/dokumentasi/Debuging/BUG1.png)


# Pengujian dan Tangkapan Layar Hasil Uji

## 1. Login ( Auth + redirect ke dashboard sesuai role)
![Gambar ini adalah halaman login dimana kita memasukkan username dan password dan setelah kita klik login kita akan masuk ke dashboard sesuai role](assets/dokumentasi/login/Login.png)
![ini adalah gambar dashboard peminjam](assets/dokumentasi/Dashboard/Peminjam.png)
![ini adalah gambar dashboard petugas](assets/dokumentasi/Dashboard/Petugas.png)
![ini adalah gambar dashboard Admin](assets/dokumentasi/Dashboard/Admin.png)

## 2. Proses Peminjaman + Konfirmasi 
![ini adalah katalog barang yang bisa dilihat oleh peminjam](assets/dokumentasi/pinjam/step1.png)
![ini adalah tampilan saat kita mengisi kolom](assets/dokumentasi/pinjam/step2.png)
![ini adalah tampilan saat kita berhasil mengajukan peminjaman](assets/dokumentasi/pinjam/step3.png)
![ini adalah tampilan halaman petugas ](assets/dokumentasi/pinjam/step4.png)
![ini adalah tampilan halaman saat petugas konfirmasi permintaan](assets/dokumentasi/pinjam/step5.png)

## 3. Proses pengembalian + konfirmasi
![ini adalah tampilan ketika user ini mengembalikan dan mengisi form pengembalian barang](assets/dokumentasi/kembalikan/step1.png)
![ini adalah tampilan petugas ketika peminjam telah menggembalikan barang](assets/dokumentasi/kembalikan/step2.png)
![ini adalah tampilan ketika petugas memeriksa records pengembalian](assets/dokumentasi/kembalikan/step3.png)
![ini adalah tampilan ketika petugas sudah konfirmasi pengembalian](assets/dokumentasi/kembalikan/step4.png)
![ini adalah tampilan munculnya record pengembalian di laporan pengembalian barang milik petuga](assets/dokumentasi/kembalikan/step5.png)

## 4. Proses CRUD ( User , Alat dan Kategori)

### Proses membuat user Baru
![ini adalah tampilan ketika admin ingin membuat user](assets/dokumentasi/user/step1.png)
![ini adalah tampilan ketika admin mengisi data user ](assets/dokumentasi/user/step2.png)
![ini adalah tampilan ketika admin berhasil membuat user](assets/dokumentasi/user/step3.png)
![untuk fitur edit kita hanya perlu memasukkan data baru ke kolom](assets/dokumentasi/user/step4.png)
![ini adalah tampilan ketika admin berhasil mengedit data user](assets/dokumentasi/user/step5.png)
![untuk fitur hapus kita hanya perlu  mengklik tombol hapus user](assets/dokumentasi/user/step6.png)
![untuk fitur konfirmasi menghapus user](assets/dokumentasi/user/step7.png)
![ini adalah tampilan ketika admin berhasil menghapus user](assets/dokumentasi/user/step8.png)
### Proses membuat Alat baru
![ini adalah tampilan ketika admin membuat alat baru](assets/dokumentasi/alat/step1.png)
![ini adalah tampilan ketika admin mengisi data alat ](assets/dokumentasi/alat/step2.png)
![untuk fitur edit kita hanya perlu memasukkan data baru ke kolom ](assets/dokumentasi/alat/step3.png)
![untuk fitur hapus kita hanya perlu  mengklik tombol hapus alat](assets/dokumentasi/alat/step4.png)
![untuk fitur konfirmasi menghapus user](assets/dokumentasi/user/step5.png)

### Proses membuat Alat kategori
![ini adalah tampilan ketika admin membuat kategori baru](assets/dokumentasi/kategori/step1.png)
![ini adalah tampilan ketika admin mengisi kategori alat ](assets/dokumentasi/kategori/step2.png)
![ini adalah tampilan ketika admin berhasil membuat kategori](assets/dokumentasi/kategori/step3.png)
![untuk fitur edit kita hanya perlu memasukkan data baru ke kolom ](assets/dokumentasi/kategori/step4.png)
![untul fitur hapus kita hanya perlu  mengklik tombol hapus kategori](assets/dokumentasi/kategori/step5.png)


## 5. Mencetak Laporan
![ini adalah tampilan ketika petugas mencetak laporan](assets/dokumentasi/laporan/step1.png)
![ini adalah tampilan ketika petugas berhasil mencetak laporan](assets/dokumentasi/laporan/step2.png)
![ini adalah tampilam dari laporan yang sudah didownload](assets/dokumentasi/laporan/step3.png)