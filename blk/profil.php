<?php
header('Content-Type: application/json');
include 'koneksi.php';

$response = array();

// Cek koneksi
if ($koneksi->connect_errno) {
    $response['success'] = false;
    $response['message'] = 'Koneksi database gagal: ' . $koneksi->connect_error;
    echo json_encode($response);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$base_url = "https://e-blk.pbltifnganjuk.com/webblk/image/";

// ========================================
// GET PROFIL
// ========================================
if ($method === 'GET') {
    $id_peserta = $_GET['id_peserta'] ?? '';

    if (empty($id_peserta)) {
        echo json_encode([
            'success' => false,
            'message' => 'ID Peserta wajib diisi'
        ]);
        exit;
    }

    $sql = "
        SELECT 
            id_peserta,
            nama_peserta,
            alamat,
            jenis_kelamin,
            tgl_lahir,
            NIK,
            NO_HP,
            email,
            id_jurusan,
            tgl_daftar,
            status,
            CASE
                WHEN foto_profil IS NULL OR foto_profil = '' THEN ''
                ELSE CONCAT(?, foto_profil)
            END AS foto_profil
        FROM peserta
        WHERE id_peserta = ?
    ";

    $stmt = $koneksi->prepare($sql);
    $stmt->bind_param("ss", $base_url, $id_peserta);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $response['success'] = true;
        $response['message'] = 'Data profil ditemukan';
        $response['profil'] = $result->fetch_assoc();
    } else {
        $response['success'] = false;
        $response['message'] = 'Data peserta tidak ditemukan';
    }

    echo json_encode($response);
    exit;
}

// ========================================
// POST - UBAH PASSWORD ATAU UPLOAD FOTO
// ========================================
elseif ($method === 'POST') {

    // ==== [1] UPLOAD FOTO PROFIL ====
    if (isset($_FILES['foto_profil'])) {
        $id_peserta = $_POST['id_peserta'] ?? '';

        if (empty($id_peserta)) {
            echo json_encode([
                'success' => false,
                'message' => 'ID Peserta wajib diisi'
            ]);
            exit;
        }

        $target_dir = "../webblk/image/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        $file_name = time() . "_" . basename($_FILES["foto_profil"]["name"]);
        $target_file = $target_dir . $file_name;

        if (move_uploaded_file($_FILES["foto_profil"]["tmp_name"], $target_file)) {
            $stmt = $koneksi->prepare("UPDATE peserta SET foto_profil = ? WHERE id_peserta = ?");
            $stmt->bind_param("ss", $file_name, $id_peserta);

            if ($stmt->execute()) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Foto profil berhasil diperbarui',
                    'foto_url' => $base_url . $file_name
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Gagal menyimpan ke database: ' . $koneksi->error
                ]);
            }
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Gagal mengupload file'
            ]);
        }

        exit;
    }
}
?>
