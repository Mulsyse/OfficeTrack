<?php
// Memulai sesi
session_start();

// Include file helpers yang berisi fungsi-fungsi penting
require_once '../config/helpers.php';
// Include kelas Database
require_once '../config/database.php';

// --- PERBAIKAN 1: Cek role pengguna yang sudah login ---
// Jika user sudah login, arahkan ke dashboard yang SESUAI perannya
if (isset($_SESSION['user']['role'])) {
    $role = $_SESSION['user']['role'];
    if ($role == 'admin') {
        header("Location: ../admin/dashboard.php");
    } elseif ($role == 'petugas') {
        header("Location: ../petugas/dashboard.php");
    } else { // Asumsi role lainnya adalah 'peminjam'
        header("Location: ../peminjam/dashboard.php");
    }
    exit();
}

// Dapatkan koneksi database dari kelas Database
 $pdo = Database::getConnection();

// Inisialisasi variabel untuk pesan error
 $error = '';

// Proses jika form dikirim dengan metode POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Ambil dan bersihkan input dari form
    $username = sanitize_input($_POST['username']);
    $password = $_POST['password'];

    // Validasi input agar tidak kosong
    if (empty($username) || empty($password)) {
        $error = "Username dan password harus diisi.";
    } else {
        // Siapkan query untuk mengambil data user berdasarkan username
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        
        // Ambil hasil query
        $user = $stmt->fetch();

        // Periksa apakah user ditemukan dan password cocok
        if ($user && password_verify($password, $user['password'])) {
            // Jika login berhasil, buat sesi untuk user
            $_SESSION['user'] = [
                'id'       => $user['id'],
                'nama'     => $user['nama'],
                'username' => $user['username'],
                'role'     => $user['role']
            ];
            
            // Catat aktivitas login ke log
            log_activity($user['id'], "User " . $user['username'] . " berhasil login");

            // --- PERBAIKAN 2: Arahkan user ke dashboard sesuai perannya ---
            if ($user['role'] == 'admin') {
                header("Location: ../admin/dashboard.php");
            } elseif ($user['role'] == 'petugas') {
                header("Location: ../petugas/dashboard.php");
            } else { // Asumsi role lainnya adalah 'peminjam'
                header("Location: ../peminjam/dashboard.php");
            }
            exit();
        } else {
            // Jika username atau password salah
            $error = "Username atau password salah.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Office Track</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- --- TAMBAHKAN CSS INI UNTUK MENGHILANGKAN SCROLLBAR --- -->
    <style>
        /* Atur agar html dan body memenuhi seluruh layar tanpa margin */
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
            overflow-x: hidden; /* Hilangkan scrollbar horizontal */
        }

        /* Gunakan Flexbox pada .login-container untuk mengatur posisi tengah */
        .login-container {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100%; /* Pastikan container mengisi tinggi body */
        }
    </style>
</head>
<body>
    <div class="login-container">
        <!-- --- PERUBAHAN: HAPUS CLASS 'min-vh-100' DARI BARIS INI --- -->
        <div class="row justify-content-center align-items-center w-100">
            <div class="col-md-6 col-lg-4">
                <div class="card shadow">
                    <div class="card-body">
                        <div class="text-center mb-4">
                            <h3 class="text-primary">Office Track</h3>
                            <p class="text-muted">Silakan login untuk melanjutkan</p>
                        </div>
                        
                        <?php if ($error): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <?php echo htmlspecialchars($error); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" class="form-control" id="username" name="username" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="remember">
                                <label class="form-check-label" for="remember">Ingat saya</label>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100">Login</button>
                        </form>
                        
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>