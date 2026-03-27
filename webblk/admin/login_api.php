<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

include 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Metode tidak diizinkan']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Data tidak valid']);
    exit;
}

$role = $input['role'] ?? '';
$password = $input['password'] ?? '';

if ($role === 'admin') {
    $username = $input['username'] ?? '';
    if (!$username || !$password) {
        echo json_encode(['success' => false, 'message' => 'Username dan password wajib diisi']);
        exit;
    }

    // 🔥 Ambil data lengkap admin: id, username, password, id_jurusan, nama_admin, foto_profil
    $stmt = $conn->prepare("SELECT id_admin, username, password, id_jurusan, nama_admin, foto_profil FROM admin WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        if (password_verify($password, $row['password'])) {
            // Simpan ke session
            $_SESSION['role'] = 'admin';
            $_SESSION['id_admin'] = $row['id_admin'];
            $_SESSION['username'] = $row['username'];
            $_SESSION['id_jurusan'] = $row['id_jurusan'];
            $_SESSION['nama_admin'] = $row['nama_admin'];

            // Tentukan path foto profil
            $foto_profil = $row['foto_profil'] ?? 'profile.png';
            $foto_profil_path = 'images/' . $foto_profil;

            // ✅ Kirim data profil lengkap ke frontend
            echo json_encode([
                'success' => true,
                'role' => 'admin',
                'username' => $row['username'],
                'nama_admin' => $row['nama_admin'],
                'foto_profil_path' => $foto_profil_path,
                'id_jurusan' => $row['id_jurusan']
            ]);
            exit;
        }
    }
    echo json_encode(['success' => false, 'message' => 'Username atau password salah']);
} 
elseif ($role === 'peserta') {
    $nik = $input['nik'] ?? '';
    if (!$nik || !$password) {
        echo json_encode(['success' => false, 'message' => 'NIK dan password wajib diisi']);
        exit;
    }

    // Ambil data lengkap peserta
    $stmt = $conn->prepare("SELECT id_peserta, NIK, nama_peserta, password FROM peserta WHERE NIK = ?");
    $stmt->bind_param("s", $nik);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        if (password_verify($password, $row['password'])) {
            $_SESSION['role'] = 'peserta';
            $_SESSION['nik'] = $row['NIK'];
            $_SESSION['nama_peserta'] = $row['nama_peserta'];

            echo json_encode([
                'success' => true,
                'role' => 'peserta',
                'username' => $row['nama_peserta'], // Atau gunakan NIK jika diinginkan
                'nama_peserta' => $row['nama_peserta']
            ]);
            exit;
        }
    }
    echo json_encode(['success' => false, 'message' => 'NIK atau password salah']);
} else {
    echo json_encode(['success' => false, 'message' => 'Peran tidak valid']);
}
?>