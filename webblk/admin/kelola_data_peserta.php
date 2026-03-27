<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include '../config/database.php';

/* =========================
   ID ADMIN FIX
========================= */
$id_admin = 1;

/* =========================
   1. GET 1 PESERTA
========================= */
if (isset($_GET['get_peserta'])) {
    header('Content-Type: application/json');

    $id = (int)$_GET['get_peserta'];
    $stmt = $conn->prepare("SELECT * FROM peserta WHERE id_peserta=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    echo json_encode($stmt->get_result()->fetch_assoc() ?: new stdClass());
    exit;
}

/* =========================
   2. IMPORT CSV
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file_csv'])) {
    header('Content-Type: application/json');

    if (!is_uploaded_file($_FILES['file_csv']['tmp_name'])) {
        echo json_encode(['status'=>'error','message'=>'File tidak valid']);
        exit;
    }

    $handle = fopen($_FILES['file_csv']['tmp_name'], 'r');
    $row = 0;
    $inserted = 0;
    $errors = [];

    while (($data = fgetcsv($handle, 1000, ',')) !== false) {
        $row++;
        if ($row === 1) continue;

        [
            $no_pendaftaran,
            $nama,
            $alamat,
            $jk,
            $tgl_lahir,
            $nik,
            $password,
            $no_hp,
            $email,
            $id_jurusan
        ] = array_pad($data, 10, null);

        if (!$nama || !$nik || strlen($nik) !== 16) {
            $errors[] = "Baris $row dilewati (data tidak valid)";
            continue;
        }

        $password = password_hash($password ?: '123456', PASSWORD_DEFAULT);
        $status = 'seleksi';

        $stmt = $conn->prepare("
            INSERT INTO peserta
            (no_pendaftaran,nama_peserta,alamat,jenis_kelamin,tgl_lahir,
             NIK,password,NO_HP,email,id_admin,id_jurusan,status)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?)
        ");

        $stmt->bind_param(
            "sssssssssiis",
            $no_pendaftaran,
            $nama,
            $alamat,
            $jk,
            $tgl_lahir,
            $nik,
            $password,
            $no_hp,
            $email,
            $id_admin,
            $id_jurusan,
            $status
        );

        if ($stmt->execute()) {
            $inserted++;
        } else {
            $errors[] = "Baris $row gagal: ".$stmt->error;
        }
    }

    fclose($handle);

    echo json_encode([
        'status'=>'success',
        'inserted'=>$inserted,
        'errors'=>$errors
    ]);
    exit;
}

/* =========================
   3. POST ACTION
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    /* === HAPUS MASSAL === */
    if (isset($_POST['hapus_massal'])) {
        $ids = array_map('intval', json_decode($_POST['hapus_massal'], true));
        $in = implode(',', array_fill(0, count($ids), '?'));

        $stmt = $conn->prepare("DELETE FROM peserta WHERE id_peserta IN ($in)");
        $stmt->bind_param(str_repeat('i', count($ids)), ...$ids);
        $stmt->execute();

        echo json_encode(['status'=>'success']);
        exit;
    }

    /* === UPDATE STATUS MASSAL === */
    if (isset($_POST['update_massal_status'])) {
        $data = json_decode($_POST['update_massal_status'], true);
        $ids = $data['ids'];
        $status = $data['status'];

        $in = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $conn->prepare("UPDATE peserta SET status=? WHERE id_peserta IN ($in)");
        $stmt->bind_param('s'.str_repeat('i', count($ids)), $status, ...$ids);
        $stmt->execute();

        echo json_encode(['status'=>'success']);
        exit;
    }

    /* === HAPUS SATU === */
    if (isset($_POST['hapus_id'])) {
        $id = (int)$_POST['hapus_id'];
        $stmt = $conn->prepare("DELETE FROM peserta WHERE id_peserta=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();

        echo json_encode(['status'=>'success']);
        exit;
    }

    /* =========================
       EDIT PESERTA (FIXED)
    ========================= */
    if (isset($_POST['id_peserta'])) {
        $stmt = $conn->prepare("
            UPDATE peserta SET
                no_pendaftaran=?,
                nama_peserta=?,
                alamat=?,
                jenis_kelamin=?,
                tgl_lahir=?,
                NIK=?,
                NO_HP=?,
                email=?,
                id_jurusan=?,
                status=?
            WHERE id_peserta=?
        ");

        $stmt->bind_param(
            "ssssssssisi",
            $_POST['no_pendaftaran'],
            $_POST['nama_peserta'],
            $_POST['alamat'],
            $_POST['jenis_kelamin'],
            $_POST['tgl_lahir'],
            $_POST['NIK'],
            $_POST['NO_HP'],
            $_POST['email'],
            $_POST['id_jurusan'],
            $_POST['status'],
            $_POST['id_peserta']
        );

        $stmt->execute();
        echo json_encode(['status'=>'success']);
        exit;
    }

    /* === TAMBAH PESERTA === */
    if (isset($_POST['nama_peserta'])) {
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $status = 'seleksi';

        $stmt = $conn->prepare("
            INSERT INTO peserta
            (no_pendaftaran,nama_peserta,alamat,jenis_kelamin,tgl_lahir,
             NIK,password,NO_HP,email,id_admin,id_jurusan,status)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?)
        ");

        $stmt->bind_param(
            "sssssssssiis",
            $_POST['no_pendaftaran'],
            $_POST['nama_peserta'],
            $_POST['alamat'],
            $_POST['jenis_kelamin'],
            $_POST['tgl_lahir'],
            $_POST['NIK'],
            $password,
            $_POST['NO_HP'],
            $_POST['email'],
            $id_admin,
            $_POST['id_jurusan'],
            $status
        );

        $stmt->execute();
        echo json_encode(['status'=>'success']);
        exit;
    }
}

/* =========================
   4. API JURUSAN
========================= */
if (isset($_GET['api']) && $_GET['api']==='jurusan') {
    header('Content-Type: application/json');
    echo json_encode(
        $conn->query("SELECT id_jurusan,nama_jurusan FROM jurusan ORDER BY nama_jurusan")
             ->fetch_all(MYSQLI_ASSOC)
    );
    exit;
}

/* =========================
   5. API PESERTA
========================= */
if (isset($_GET['api']) && $_GET['api']==='peserta') {
    header('Content-Type: application/json');

    $search = "%".($_GET['search'] ?? '')."%";
    $jurusan = $_GET['jurusan'] ?? 'all';

    $sql = "
        SELECT p.*, j.nama_jurusan
        FROM peserta p
        LEFT JOIN jurusan j ON p.id_jurusan=j.id_jurusan
        WHERE (?='all' OR j.nama_jurusan=?)
        AND (p.nama_peserta LIKE ? OR p.NIK LIKE ? OR p.no_pendaftaran LIKE ?)
        ORDER BY p.id_peserta DESC
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssss",$jurusan,$jurusan,$search,$search,$search);
    $stmt->execute();

    echo json_encode($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
    exit;
}

http_response_code(400);
echo json_encode(['status'=>'error']);
