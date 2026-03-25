<?php
header('Content-Type: application/json');
include 'koneksi.php';

$response = [];

// Jika koneksi gagal
if ($koneksi->connect_errno) {
    echo json_encode([
        'success' => false,
        'message' => 'Koneksi database gagal: ' . $koneksi->connect_error
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $NIK = $_POST['NIK'] ?? '';
    $password = $_POST['password'] ?? '';

    if ($NIK == '' || $password == '') {
        echo json_encode([
            'success' => false,
            'message' => 'NIK dan password harus diisi!'
        ]);
        exit;
    }

    // Ambil berdasarkan NIK saja
    $stmt = $koneksi->prepare("SELECT * FROM peserta WHERE NIK = ? LIMIT 1");
    $stmt->bind_param("s", $NIK);
    $stmt->execute();
    $result = $stmt->get_result();

    // Jika NIK tidak ditemukan
    if ($result->num_rows === 0) {
        echo json_encode([
            'success' => false,
            'message' => 'NIK tidak ditemukan!'
        ]);
        exit;
    }

    $row = $result->fetch_assoc();
    $hash_db = $row['password']; // HASH dari database

    // Verifikasi password hash
    if (password_verify($password, $hash_db)) {

        echo json_encode([
            'success' => true,
            'message' => 'Login berhasil',
            'datapeserta' => [
                'id_peserta' => $row['id_peserta'],
                'nama_peserta' => $row['nama_peserta'],
                'NIK' => $row['NIK'],
                'no_hp' => $row['NO_HP']
            ]
        ]);

    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Password salah!'
        ]);
    }

} else {
    echo json_encode([
        'success' => false,
        'message' => 'Metode tidak valid!'
    ]);
}
?>
