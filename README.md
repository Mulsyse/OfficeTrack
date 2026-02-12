# Aplikasi Peminjaman Alat

Sistem Peminjaman Alat berbasis web menggunakan PHP Native dan MySQL yang dirancang untuk mengelola peminjaman peralatan dengan sistem multi-level pengguna.

## 📋 Fitur

### 🔐 Level Pengguna
Sistem memiliki 3 level pengguna dengan hak akses berbeda:

- **Admin**: Akses penuh ke semua fitur
- **Petugas**: Mengelola peminjaman dan pengembalian
- **Peminjam**: Melihat dan mengajukan peminjaman alat

### ✅ Fitur Berdasarkan Role

#### Fitur Umum (Semua User)
- ✅ Login dengan session
- ✅ Logout aman

#### Admin
- ✅ CRUD User (Tambah, Edit, Hapus pengguna)
- ✅ CRUD Alat (Kelola data peralatan)
- ✅ CRUD Kategori (Kelola kategori alat)
- ✅ CRUD Data Peminjaman
- ✅ CRUD Data Pengembalian
- ✅ Melihat Log Aktivitas User
- ✅ Dashboard dengan statistik lengkap

#### Petugas
- ✅ Menyetujui / Menolak Peminjaman
- ✅ Memantau Pengembalian Alat
- ✅ Mencetak Laporan Peminjaman & Pengembalian (PDF/Print)
- ✅ Dashboard dengan statistik peminjaman

#### Peminjam
- ✅ Melihat Daftar Alat tersedia
- ✅ Mengajukan Peminjaman Alat
- ✅ Mengembalikan Alat
- ✅ Melihat Riwayat Peminjaman
- ✅ Dashboard personal

## 🗄️ Database

### Struktur Tabel
- `users` - Data pengguna (admin, petugas, peminjam)
- `alat` - Data peralatan yang dipinjamkan
- `kategori` - Kategori peralatan
- `peminjaman` - Data transaksi peminjaman
- `pengembalian` - Data transaksi pengembalian
- `log_aktivitas` - Log aktivitas pengguna

## 🛠️ Teknologi

- **Backend**: PHP 7.4+ (Native)
- **Database**: MySQL/MariaDB
- **Frontend**: HTML5, CSS3, Bootstrap 5
- **Authentication**: Session-based dengan password hashing
- **Security**: Input sanitization, SQL Injection prevention

## 📁 Struktur Folder

```
ukk/
├── admin/                  # Halaman Admin
│   ├── dashboard.php
│   ├── users.php
│   ├── user_tambah.php
│   ├── user_edit.php
│   ├── alat.php
│   ├── alat_tambah.php
│   ├── alat_edit.php
│   ├── kategori.php
│   ├── peminjaman.php
│   ├── peminjaman_detail.php
│   ├── pengembalian.php
│   ├── pengembalian_detail.php
│   └── log_activity.php
├── auth/                   # Autentikasi
│   ├── login.php
│   ├── logout.php
│   └── unauthorized.php
├── config/                 # Konfigurasi
│   └── database.php
├── petugas/                # Halaman Petugas
│   ├── dashboard.php
│   ├── peminjaman.php
│   ├── peminjaman_detail.php
│   ├── pengembalian.php
│   ├── laporan_peminjaman.php
│   └── laporan_pengembalian.php
├── peminjam/               # Halaman Peminjam
│   ├── dashboard.php
│   ├── daftar_alat.php
│   ├── get_alat_detail.php
│   ├── pinjam_alat.php
│   ├── peminjaman_saya.php
│   ├── peminjaman_detail.php
│   ├── pengembalian.php
│   └── proses_pengembalian.php
├── assets/                 # Assets
│   ├── css/
│   │   └── style.css
│   ├── js/
│   │   └── script.js
│   └── images/
├── database.sql            # Script database
└── index.php               # Entry point
```

## 🚀 Instalasi

### 1. Persyaratan
- PHP 7.4 atau lebih tinggi
- MySQL/MariaDB 5.7 atau lebih tinggi
- Web Server (Apache/Nginx)
- Composer (opsional)

### 2. Setup Database
1. Buat database baru di MySQL:
   ```sql
   CREATE DATABASE peminjaman_alat;
   ```

2. Import file database:
   ```bash
   mysql -u username -p peminjaman_alat < database.sql
   ```

### 3. Konfigurasi Database
Edit file `config/database.php` sesuai dengan konfigurasi database Anda:

```php
private $host = "localhost";
private $db_name = "peminjaman_alat";
private $username = "root";
private $password = "";
```

### 4. Setup Web Server
1. Copy folder proyek ke document root web server
2. Pastikan permission folder benar:
   ```bash
   chmod 755 -R ukk/
   ```

### 5. Akses Aplikasi
Buka browser dan akses:
```
http://localhost/ukk/
```

## 👤 Default Login

| Role | Username | Password |
|------|----------|----------|
| Admin | admin | admin123 |
| Petugas | petugas | petugas123 |
| Peminjam | peminjam | peminjam123 |

## 🔧 Konfigurasi

### Database Configuration
Edit file `config/database.php` untuk menyesuaikan koneksi database:

```php
private $host = "localhost";        // Host database
private $db_name = "peminjaman_alat"; // Nama database
private $username = "root";         // Username database
private $password = "";             // Password database
```

### Session Configuration
Session telah dikonfigurasi dengan aman menggunakan:
- Secure session handling
- Timeout management
- Role-based access control

## 📝 Alur Sistem

### 1. Alur Peminjaman
1. **Peminjam** login ke sistem
2. Melihat daftar alat yang tersedia
3. Mengajukan peminjaman dengan form
4. **Petugas** menerima notifikasi peminjaman baru
5. **Petugas** menyetujui/menolak peminjaman
6. **Peminjam** menerima notifikasi status
7. Jika disetujui, peminjam dapat mengambil alat

### 2. Alur Pengembalian
1. **Peminjam** mengajukan pengembalian
2. **Petugas** memproses pengembalian
3. **Petugas** memeriksa kondisi alat
4. Update status dan stok alat
5. **Admin** dapat melihat laporan lengkap

### 3. Alur Manajemen
1. **Admin** mengelola user, alat, dan kategori
2. **Admin** memantau semua aktivitas melalui log
3. **Petugas** mengelola transaksi harian
4. **Peminjam** mengakses fitur peminjaman personal

## 🔒 Keamanan

### Implementasi Keamanan:
- ✅ Password hashing menggunakan `password_hash()`
- ✅ Input sanitization dengan `htmlspecialchars()`
- ✅ SQL Injection prevention dengan prepared statements
- ✅ Session-based authentication
- ✅ Role-based access control
- ✅ CSRF protection pada form critical
- ✅ Input validation pada semua form

### Best Practices:
- ✅ Never trust user input
- ✅ Always validate and sanitize
- ✅ Use prepared statements for database queries
- ✅ Implement proper error handling
- ✅ Regular security updates

## 🐛 Troubleshooting

### Common Issues:

#### 1. Database Connection Error
**Problem**: "Connection failed"
**Solution**: 
- Check database credentials in `config/database.php`
- Ensure MySQL service is running
- Verify database exists and user has permissions

#### 2. Session Issues
**Problem**: "Access denied" or redirect loops
**Solution**:
- Check session configuration in php.ini
- Ensure session.save_path is writable
- Clear browser cookies and cache

#### 3. Permission Issues
**Problem**: "Access denied" or file not found
**Solution**:
- Check file permissions (755 for folders, 644 for files)
- Ensure web server has read access
- Check .htaccess configuration

#### 4. Blank Pages
**Problem**: White screen with no content
**Solution**:
- Enable error reporting in PHP
- Check PHP error logs
- Verify syntax in PHP files

## 📞 Support

Jika mengalami masalah atau memiliki pertanyaan:

1. Periksa bagian Troubleshooting
2. Lihat error logs di server
3. Pastikan semua persyaratan terpenuhi
4. Test dengan default login credentials

## 🔄 Update & Maintenance

### Regular Maintenance:
- Backup database secara berkala
- Update PHP dan dependencies
- Monitor log aktivitas
- Clean up old records jika perlu

### Backup Database:
```bash
mysqldump -u username -p peminjaman_alat > backup_$(date +%Y%m%d).sql
```

## 📄 License

Proyek ini dibuat untuk tujuan pembelajaran dan pengembangan. 
Feel free to modify and distribute sesuai kebutuhan.

## 👨‍💻 Developer

Dikembangkan dengan PHP Native, MySQL, dan Bootstrap 5.
Fokus pada keamanan, usability, dan maintainability.

---

**Happy Coding! 🚀**
