<?php
// 1. Mulai sesi
session_start();

// 2. Include file yang diperlukan
require_once '../config/helpers.php';
require_once '../config/database.php';

// 3. CEK KEAMANAN: Pastikan user sudah login dan role-nya 'peminjam'
check_login();
check_role('peminjam');

// 4. Inisialisasi variabel output
 $output = '';

// 5. Proses jika ID alat dikirim via GET
if (isset($_GET['id'])) {
    // PERBAIKAN: Validasi ID untuk memastikan itu adalah angka yang valid
    $alat_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

    if ($alat_id) {
        // --- PERUBAHAN KE PDO ---
        // 6. Ambil koneksi database (menggunakan PDO)
        $pdo = Database::getConnection();

        // 7. Siapkan dan eksekusi query dengan prepared statement
        $sql = "SELECT a.*, k.nama_kategori 
                FROM alat a 
                LEFT JOIN kategori k ON a.kategori_id = k.id 
                WHERE a.id = ?";
        
        $stmt = $pdo->prepare($sql);
        // Eksekusi dengan melewatkan parameter dalam bentuk array
        $stmt->execute([$alat_id]);
        // Ambil satu baris hasil sebagai array asosiatif
        $alat = $stmt->fetch(PDO::FETCH_ASSOC);

        // Tidak perlu menutup koneksi secara manual di PDO

        // 8. Tampilkan data jika ditemukan
        if ($alat) {
            // Gunakan htmlspecialchars() untuk mencegah XSS
            $output .= '<div class="row">';
            $output .= '<div class="col-md-5"><strong>Nama Alat:</strong></div>';
            $output .= '<div class="col-md-7">' . htmlspecialchars($alat['nama_alat']) . '</div>';
            $output .= '</div>';
            
            $output .= '<div class="row mb-2">';
            $output .= '<div class="col-md-5"><strong>Kategori:</strong></div>';
            $output .= '<div class="col-md-7">' . htmlspecialchars($alat['nama_kategori'] ?: 'Tidak ada kategori') . '</div>';
            $output .= '</div>';
            
            $output .= '<div class="row mb-2">';
            $output .= '<div class="col-md-5"><strong>Stok Tersedia:</strong></div>';
            $stokBadgeClass = $alat['stok'] > 5 ? 'success' : ($alat['stok'] > 0 ? 'warning' : 'danger');
            $output .= '<div class="col-md-7"><span class="badge bg-' . $stokBadgeClass . '">' . htmlspecialchars($alat['stok']) . '</span></div>';
            $output .= '</div>';
            
            $output .= '<div class="row mb-2">';
            $output .= '<div class="col-md-5"><strong>Kondisi:</strong></div>';
            $kondisiBadgeClass = $alat['kondisi'] == 'baik' ? 'success' : ($alat['kondisi'] == 'rusak_ringan' ? 'warning' : 'danger');
            // Ganti underscore dengan spasi dan amankan output
            $kondisiText = htmlspecialchars(ucfirst(str_replace('_', ' ', $alat['kondisi'])));
            $output .= '<div class="col-md-7"><span class="badge bg-' . $kondisiBadgeClass . '">' . $kondisiText . '</span></div>';
            $output .= '</div>';
            
            $output .= '<div class="row mb-2">';
            $output .= '<div class="col-md-5"><strong>Deskripsi:</strong></div>';
            $output .= '<div class="col-md-7">' . htmlspecialchars($alat['deskripsi'] ?: 'Tidak ada deskripsi') . '</div>';
            $output .= '</div>';
            
            $output .= '<div class="row">';
            $output .= '<div class="col-md-5"><strong>ID Alat:</strong></div>';
            $output .= '<div class="col-md-7">#' . htmlspecialchars(str_pad($alat['id'], 4, '0', STR_PAD_LEFT)) . '</div>';
            $output .= '</div>';

        } else {
            // Tampilkan pesan jika data tidak ditemukan
            $output = '<p class="text-center text-danger">Data alat tidak ditemukan.</p>';
        }
    } else {
        $output = '<p class="text-center text-danger">ID Alat tidak valid.</p>';
    }
} else {
    $output = '<p class="text-center text-danger">ID Alat tidak disertakan.</p>';
}

// 9. Cetak output yang sudah dibangun
echo $output;
?>