<?php
// Fix passwords script
$host = "localhost";
$db_name = "peminjaman_alat";
$username = "root";
$password = "";

$conn = new mysqli($host, $username, $password, $db_name);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Update passwords
$updates = [
    'admin' => password_hash('admin123', PASSWORD_DEFAULT),
    'petugas' => password_hash('petugas123', PASSWORD_DEFAULT),
    'peminjam' => password_hash('peminjam123', PASSWORD_DEFAULT)
];

foreach ($updates as $user => $hash) {
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE username = ?");
    $stmt->bind_param("ss", $hash, $user);
    $stmt->execute();
    echo "Updated password for $user\n";
    $stmt->close();
}

echo "Passwords updated successfully!\n";
echo "Login credentials:\n";
echo "Admin: admin/admin123\n";
echo "Petugas: petugas/petugas123\n";
echo "Peminjam: peminjam/peminjam123\n";

$conn->close();
?>
