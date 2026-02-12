<?php
session_start();
require_once '../config/database.php';

// Check login and role
check_login();
check_role('peminjam');

$db = new Database();
$conn = $db->getConnection();
$user_id = $_SESSION['user_id'];

// Get alat detail for modal
if (isset($_GET['id'])) {
    $alat_id = $_GET['id'];
    $stmt = $conn->prepare("SELECT a.*, k.nama_kategori FROM alat a LEFT JOIN kategori k ON a.kategori_id = k.id WHERE a.id = ?");
    $stmt->bind_param("i", $alat_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $alat = $result->fetch_assoc();
    $stmt->close();
    
    if ($alat) {
        echo '<div class="row">';
        echo '<div class="col-md-6"><strong>Nama Alat:</strong></div>';
        echo '<div class="col-md-6">' . $alat['nama_alat'] . '</div>';
        echo '</div>';
        
        echo '<div class="row mb-2">';
        echo '<div class="col-md-6"><strong>Kategori:</strong></div>';
        echo '<div class="col-md-6">' . ($alat['nama_kategori'] ?: 'Tidak ada kategori') . '</div>';
        echo '</div>';
        
        echo '<div class="row mb-2">';
        echo '<div class="col-md-6"><strong>Stok Tersedia:</strong></div>';
        echo '<div class="col-md-6"><span class="badge bg-' . ($alat['stok'] > 5 ? 'success' : ($alat['stok'] > 0 ? 'warning' : 'danger')) . '">' . $alat['stok'] . '</span></div>';
        echo '</div>';
        
        echo '<div class="row mb-2">';
        echo '<div class="col-md-6"><strong>Kondisi:</strong></div>';
        echo '<div class="col-md-6"><span class="badge bg-' . ($alat['kondisi'] == 'baik' ? 'success' : ($alat['kondisi'] == 'rusak_ringan' ? 'warning' : 'danger')) . '">' . ucfirst($alat['kondisi']) . '</span></div>';
        echo '</div>';
        
        echo '<div class="row mb-2">';
        echo '<div class="col-md-6"><strong>Deskripsi:</strong></div>';
        echo '<div class="col-md-6">' . ($alat['deskripsi'] ?: 'Tidak ada deskripsi') . '</div>';
        echo '</div>';
        
        echo '<div class="row">';
        echo '<div class="col-md-6"><strong>ID Alat:</strong></div>';
        echo '<div class="col-md-6">#' . str_pad($alat['id'], 4, '0', STR_PAD_LEFT) . '</div>';
        echo '</div>';
    }
}
?>
