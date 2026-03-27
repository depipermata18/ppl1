<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
header('Content-Type: application/json; charset=utf-8');

// =======================================================
// CHECK AUTH (sama seperti dashboard)
// =======================================================
if (
    !isset($_SESSION['id_admin']) ||
    !isset($_SESSION['role']) ||
    $_SESSION['role'] !== 'admin'
) {
    http_response_code(403);
    echo json_encode(['error' => 'Akses ditolak. Silakan login terlebih dahulu.']);
    exit;
}

// =======================================================
// INCLUDE DATABASE (samakan dengan dashboard)
// =======================================================
include '../config/database.php';  // sesuaikan lokasi file

if (!isset($conn) || $conn->connect_error) {
    http_response_code(500);
    echo json_encode(['error' => 'Koneksi database gagal.']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$id_admin = (int)$_SESSION['id_admin'];

// folder gambar: filesystem path & web path (relative)
$imageDir = __DIR__ . '/../images/';        // untuk file_exists() dan move_uploaded_file()
$webImagePathPrefix = '../images/';         // untuk memberi path agar frontend bisa menampilkan gambar
$defaultImage = 'profile.png';

switch ($method) {

    case 'GET':
        $stmt = $conn->prepare("
            SELECT 
                id_admin, 
                nama_admin, 
                username, 
                email, 
                foto_profil, 
                tgl_lahir, 
                jenis_kelamin, 
                no_hp, 
                role, 
                status, 
                alamat,
                id_jurusan
            FROM admin 
            WHERE id_admin = ?
        ");
        if (!$stmt) {
            http_response_code(500);
            echo json_encode(['error' => 'Gagal menyiapkan query.']);
            exit;
        }

        $stmt->bind_param("i", $id_admin);
        $stmt->execute();
        $result = $stmt->get_result();
        $admin = $result->fetch_assoc();
        $stmt->close();

        if (!$admin) {
            http_response_code(404);
            echo json_encode(['error' => 'Data admin tidak ditemukan.']);
            exit;
        }

        // Ambil nama jurusan (opsional)
        if (!empty($admin['id_jurusan'])) {
            $stmt_jur = $conn->prepare("SELECT nama_jurusan FROM jurusan WHERE id_jurusan = ?");
            if ($stmt_jur) {
                $stmt_jur->bind_param("i", $admin['id_jurusan']);
                $stmt_jur->execute();
                $r = $stmt_jur->get_result()->fetch_assoc();
                $admin['nama_jurusan'] = $r['nama_jurusan'] ?? 'Tidak Diketahui';
                $stmt_jur->close();
            } else {
                $admin['nama_jurusan'] = 'Tidak Diketahui';
            }
        } else {
            $admin['nama_jurusan'] = 'Belum Ditentukan';
        }

        // Tambahkan path foto profil (web path) dan cek di filesystem
        if (!empty($admin['foto_profil']) && file_exists($imageDir . $admin['foto_profil'])) {
            $admin['foto_profil_path'] = $webImagePathPrefix . $admin['foto_profil'];
        } else {
            $admin['foto_profil_path'] = $webImagePathPrefix . $defaultImage;
        }

        // Jangan kirim field sensitif (meskipun kita tidak pilih password di SELECT)
        if (isset($admin['password'])) unset($admin['password']);

        echo json_encode(['admin' => $admin], JSON_UNESCAPED_UNICODE);
        break;


    case 'POST':
        // Upload foto profil dilakukan via POST multipart/form-data
        if (!isset($_FILES['foto_profil'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Tidak ada file foto yang diunggah.']);
            exit;
        }

        $file = $_FILES['foto_profil'];
        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
        $max_size = 5 * 1024 * 1024; // 5MB

        if (!in_array($file['type'], $allowed_types)) {
            http_response_code(400);
            echo json_encode(['error' => 'Format file tidak didukung. Gunakan JPEG atau PNG.']);
            exit;
        }

        if ($file['size'] > $max_size) {
            http_response_code(400);
            echo json_encode(['error' => 'Ukuran file terlalu besar. Maksimal 5MB.']);
            exit;
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $newName = 'admin_' . $id_admin . '_' . time() . '.' . $ext;

        // pastikan folder ada
        if (!is_dir($imageDir)) {
            if (!mkdir($imageDir, 0755, true)) {
                http_response_code(500);
                echo json_encode(['error' => 'Gagal membuat folder tujuan.']);
                exit;
            }
        }

        $targetFile = $imageDir . $newName;

        if (!move_uploaded_file($file['tmp_name'], $targetFile)) {
            http_response_code(500);
            echo json_encode(['error' => 'Gagal mengunggah file.']);
            exit;
        }

        // ambil nama foto lama untuk dihapus jika perlu
        $stmt_old = $conn->prepare("SELECT foto_profil FROM admin WHERE id_admin = ?");
        if ($stmt_old) {
            $stmt_old->bind_param("i", $id_admin);
            $stmt_old->execute();
            $old = $stmt_old->get_result()->fetch_assoc();
            $stmt_old->close();
            $oldFoto = $old['foto_profil'] ?? null;
        } else {
            $oldFoto = null;
        }

        // simpan nama file baru ke db
        $stmt_update_foto = $conn->prepare("UPDATE admin SET foto_profil = ? WHERE id_admin = ?");
        if (!$stmt_update_foto) {
            // rollback file
            if (file_exists($targetFile)) unlink($targetFile);
            http_response_code(500);
            echo json_encode(['error' => 'Gagal menyiapkan query update foto.']);
            exit;
        }
        $stmt_update_foto->bind_param("si", $newName, $id_admin);
        if ($stmt_update_foto->execute()) {
            $stmt_update_foto->close();

            // jika foto lama bukan default, hapus
            if ($oldFoto && $oldFoto !== $defaultImage && file_exists($imageDir . $oldFoto)) {
                @unlink($imageDir . $oldFoto);
            }

            echo json_encode([
                'success' => true,
                'foto_profil' => $newName,
                'foto_profil_path' => $webImagePathPrefix . $newName
            ], JSON_UNESCAPED_UNICODE);
            exit;
        } else {
            // hapus file baru bila gagal update db
            if (file_exists($targetFile)) unlink($targetFile);
            http_response_code(500);
            echo json_encode(['error' => 'Gagal menyimpan data ke database.']);
            exit;
        }

        break;


    case 'PUT':
        // PUT: pembaruan profil atau password (client harus set Content-Type: application/json)
        $input = json_decode(file_get_contents('php://input'), true);

        if (!is_array($input) || empty($input)) {
            http_response_code(400);
            echo json_encode(['error' => 'Data input tidak valid.']);
            exit;
        }

        // Ganti password
        if (isset($input['password'])) {
            $newPassword = trim($input['password']);

            if (empty($newPassword)) {
                http_response_code(400);
                echo json_encode(['error' => 'Password baru tidak boleh kosong.']);
                exit;
            }

            if (strlen($newPassword) < 6) {
                http_response_code(400);
                echo json_encode(['error' => 'Password baru harus minimal 6 karakter.']);
                exit;
            }

            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

            $stmt_update_pass = $conn->prepare("UPDATE admin SET password = ? WHERE id_admin = ?");
            if (!$stmt_update_pass) {
                http_response_code(500);
                echo json_encode(['error' => 'Gagal menyiapkan query update password.']);
                exit;
            }
            $stmt_update_pass->bind_param("si", $hashedPassword, $id_admin);
            if ($stmt_update_pass->execute() && $stmt_update_pass->affected_rows >= 0) {
                $stmt_update_pass->close();
                echo json_encode(['success' => true, 'message' => 'Password berhasil diperbarui.']);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Gagal memperbarui password.']);
            }
            exit;
        }

        // Update profil biasa
        $nama_admin = trim($input['nama_admin'] ?? '');
        $email = trim($input['email'] ?? '');
        $tgl_lahir = $input['tgl_lahir'] ?? null;
        $jenis_kelamin = $input['jenis_kelamin'] ?? null;
        $no_hp = trim($input['no_hp'] ?? '');
        $alamat = trim($input['alamat'] ?? '');

        if (empty($nama_admin)) {
            http_response_code(400);
            echo json_encode(['error' => 'Nama admin wajib diisi.']);
            exit;
        }

        if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode(['error' => 'Format email tidak valid.']);
            exit;
        }

        if (!empty($no_hp) && !preg_match('/^[0-9\-\+\s\(\)]+$/', $no_hp)) {
            http_response_code(400);
            echo json_encode(['error' => 'Format nomor HP tidak valid.']);
            exit;
        }

        $stmt_update_profil = $conn->prepare("
            UPDATE admin 
            SET nama_admin = ?, email = ?, tgl_lahir = ?, jenis_kelamin = ?, no_hp = ?, alamat = ?
            WHERE id_admin = ?
        ");
        if (!$stmt_update_profil) {
            http_response_code(500);
            echo json_encode(['error' => 'Gagal menyiapkan query update profil.']);
            exit;
        }
        $stmt_update_profil->bind_param("ssssssi", $nama_admin, $email, $tgl_lahir, $jenis_kelamin, $no_hp, $alamat, $id_admin);

        if ($stmt_update_profil->execute()) {
            $affected = $stmt_update_profil->affected_rows;
            $stmt_update_profil->close();

            if ($affected > 0) {
                echo json_encode(['success' => true, 'message' => 'Profil berhasil diperbarui.']);
            } else {
                // tidak berubah
                echo json_encode(['success' => true, 'message' => 'Profil tidak berubah.']);
            }
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Gagal memperbarui profil.']);
        }

        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Metode HTTP tidak diizinkan.']);
        break;
}
