<?php
header('Content-Type: application/json');
include 'koneksi.php';

$response = array();

if ($koneksi->connect_errno) {
    $response['success'] = false;
    $response['message'] = 'Koneksi database gagal: ' . $koneksi->connect_error;
    echo json_encode($response);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $id_peserta = $_GET['id_peserta'] ?? '';

    if ($id_peserta == '') {
        $response['success'] = false;
        $response['message'] = 'ID Peserta tidak ditemukan';
        echo json_encode($response);
        exit;
    }

    try {
        // URL dasar folder foto
        $base_url = "https://e-blk.pbltifnganjuk.com/webblk/image/";

        $q_peserta = $koneksi->prepare("
                SELECT 
            id_peserta,
            nama_peserta,
            jenis_kelamin,
            tgl_lahir,
            id_jurusan,
            CASE
                WHEN foto_profil IS NULL OR foto_profil = '' THEN ''
                ELSE CONCAT(?, foto_profil)
            END AS foto_profil
        FROM peserta
        WHERE id_peserta = ?
        ");
        if (!$q_peserta) throw new Exception($koneksi->error);

        $q_peserta->bind_param("ss", $base_url, $id_peserta);
        $q_peserta->execute();
        $res_peserta = $q_peserta->get_result();

        if ($res_peserta->num_rows > 0) {
            $peserta = $res_peserta->fetch_assoc();
            $id_jurusan = $peserta['id_jurusan'];
            $today = date('Y-m-d H:i:s');

            $q_jadwal = $koneksi->prepare("
                SELECT 
                    id_jadwal, 
                    nama_jadwal AS nama_kegiatan, 
                    waktu_mulai, 
                    waktu_selesai, 
                    keterangan,
                    CASE
                        WHEN ? BETWEEN waktu_mulai AND waktu_selesai THEN 'sedang dilaksanakan'
                        ELSE 'belum dimulai'
                    END AS status
                FROM jadwal
                WHERE id_jurusan = ?
                ORDER BY waktu_mulai ASC
            ");
            if (!$q_jadwal) throw new Exception($koneksi->error);

            $q_jadwal->bind_param("ss", $today, $id_jurusan);
            $q_jadwal->execute();
            $res_jadwal = $q_jadwal->get_result();

            $jadwal = [];
            while ($row = $res_jadwal->fetch_assoc()) {
                $jadwal[] = $row;
            }

            $response['success'] = true;
            $response['message'] = 'Data ditemukan';
            $response['peserta'] = $peserta;
            $response['jadwal'] = $jadwal;
        } else {
            $response['success'] = false;
            $response['message'] = 'Peserta tidak ditemukan';
        }
    } catch (Exception $e) {
        $response['success'] = false;
        $response['message'] = 'Kesalahan query: ' . $e->getMessage();
    }
} else {
    $response['success'] = false;
    $response['message'] = 'Metode tidak valid';
}

echo json_encode($response);
?>
