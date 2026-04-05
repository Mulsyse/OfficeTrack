<?php
// includes/DatabaseCallbacks.php
class DatabaseCallbacks {
    private $conn;

    public function __construct($db_connection) {
        $this->conn = $db_connection;
    }

    // Dipanggil setelah peminjaman berhasil
    public function afterPeminjamanSuccess($id_peminjaman, $id_user) {
        // Kurangi stok alat
        $this->updateStokAlat($id_peminjaman, 'kurangi');
        
        // Kirim notifikasi ke petugas (opsional, contoh)
        $this->sendNotification(1, "Peminjaman Baru", "Ada peminjaman baru dengan ID: $id_peminjaman menunggu persetujuan.");
        
        // Log aktivitas
        $this->logCustomActivity($id_user, "Mengajukan peminjaman baru dengan ID: $id_peminjaman");
    }
    
    // Dipanggil setelah pengembalian berhasil
    public function afterPengembalianSuccess($id_peminjaman, $id_user) {
        // Kembalikan stok alat
        $this->updateStokAlat($id_peminjaman, 'tambah');
        
        // Log aktivitas
        $this->logCustomActivity($id_user, "Mengembalikan peminjaman dengan ID: $id_peminjaman");
    }
    
    // Private method untuk update stok
    private function updateStokAlat($id_peminjaman, $aksi) {
        $query = "SELECT id_alat, jumlah FROM detail_peminjaman WHERE id_peminjaman = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $id_peminjaman);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $id_alat = $row['id_alat'];
            $jumlah = $row['jumlah'];
            
            if ($aksi == 'kurangi') {
                $update_query = "UPDATE alat SET stok = stok - ? WHERE id = ?";
            } else {
                $update_query = "UPDATE alat SET stok = stok + ? WHERE id = ?";
            }
            
            $update_stmt = $this->conn->prepare($update_query);
            $update_stmt->bind_param("ii", $jumlah, $id_alat);
            $update_stmt->execute();
        }
    }
    
    // Private method untuk notifikasi (contoh sederhana)
    private function sendNotification($id_user, $judul, $pesan) {
        // Asumsi Anda punya tabel notifikasi
        $query = "INSERT INTO notifikasi (id_user, judul, pesan, is_read, created_at) VALUES (?, ?, ?, 0, NOW())";
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("iss", $id_user, $judul, $pesan);
            $stmt->execute();
        } catch (Exception $e) {
            // Abaikan jika tabel notifikasi tidak ada
        }
    }
    
    // Private method untuk log kustom
    private function logCustomActivity($id_user, $aktivitas) {
        $query = "INSERT INTO log_activity (id_user, aktivitas, created_at) VALUES (?, ?, NOW())";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("is", $id_user, $aktivitas);
        $stmt->execute();
    }
}