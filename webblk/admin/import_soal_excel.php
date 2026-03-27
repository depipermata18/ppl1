<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include '../config/database.php';
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

header('Content-Type: application/json');

// ================= CEK LOGIN ADMIN =================
if (!isset($_SESSION['id_admin']) || empty($_SESSION['id_admin'])) {
    echo json_encode(['status' => 'error', 'message' => 'Anda belum login sebagai admin.']);
    exit;
}

$id_admin = $_SESSION['id_admin']; // dari session
$id_jurusan = $_POST['id_jurusan'] ?? ''; // dari URL/form

if (empty($id_jurusan)) {
    echo json_encode(['status' => 'error', 'message' => 'ID jurusan tidak ditemukan.']);
    exit;
}

// Validasi file
if (!isset($_FILES['file_excel']) || empty($_FILES['file_excel']['tmp_name'])) {
    echo json_encode(['status' => 'error', 'message' => 'File Excel tidak ditemukan.']);
    exit;
}

$allowedExtensions = ['xls', 'xlsx'];
$ext = strtolower(pathinfo($_FILES['file_excel']['name'], PATHINFO_EXTENSION));
if (!in_array($ext, $allowedExtensions)) {
    echo json_encode(['status' => 'error', 'message' => 'Hanya file .xls/.xlsx yang diizinkan.']);
    exit;
}

$upload_dir_server = __DIR__ . '/soal/';
$upload_dir_url = 'soal/';
if (!is_dir($upload_dir_server)) mkdir($upload_dir_server, 0777, true);

$inserted = 0;
$errors = [];

try {
    $spreadsheet = IOFactory::load($_FILES['file_excel']['tmp_name']);
    $sheet = $spreadsheet->getActiveSheet();

    // Ambil gambar dari drawing (sel A)
    $gambarMap = [];
    foreach ($sheet->getDrawingCollection() as $drawing) {
        $cell = $drawing->getCoordinates();
        $row = (int)preg_replace('/[^0-9]/', '', $cell);
        if ($row < 2) continue;

        $filename = 'soal_' . uniqid() . '.png';
        $target = $upload_dir_server . $filename;

        if ($drawing instanceof \PhpOffice\PhpSpreadsheet\Worksheet\MemoryDrawing) {
            imagepng($drawing->getImageResource(), $target);
        } else {
            copy($drawing->getPath(), $target);
        }
        $gambarMap[$row] = $upload_dir_url . $filename;
    }

    // Ambil nomor soal terakhir
    $stmtNo = $conn->prepare("SELECT IFNULL(MAX(no_soal), 0) FROM seleksi WHERE id_jurusan = ?");
    $stmtNo->bind_param("s", $id_jurusan);
    $stmtNo->execute();
    $stmtNo->bind_result($no_soal);
    $stmtNo->fetch();
    $stmtNo->close();

    // Baca data
    $rows = $sheet->toArray(null, true, true, true);
    $rowNumber = 0;

    foreach ($rows as $row) {
        $rowNumber++;
        if ($rowNumber === 1) continue; // Skip header

        // Ambil hanya sampai kolom D (A, B, C, D)
        $soal  = trim($row['A'] ?? '');
        $opsiA = trim($row['B'] ?? '');
        $opsiB = trim($row['C'] ?? '');
        $opsiC = trim($row['D'] ?? '');
        $opsiD = trim($row['E'] ?? ''); // Kolom E = Opsi D
        $kunci = strtoupper(trim($row['F'] ?? '')); // Kolom F = Kunci Jawaban
        $poin  = (int)($row['G'] ?? 0); // Kolom G = Poin
        $wm    = !empty($row['H']) ? trim($row['H']) : null; // Kolom H = Waktu Mulai
        $ws    = !empty($row['I']) ? trim($row['I']) : null; // Kolom I = Waktu Selesai
        // Kolom J+ diabaikan

        // Validasi: hanya butuh A-D
        if (!$soal || !$opsiA || !$opsiB || !$opsiC || !$opsiD) {
            $errors[] = "Baris $rowNumber di-skip: soal/opsi tidak lengkap (harus A-D)";
            continue;
        }

        // Validasi kunci: hanya A, B, C, D
        if (!in_array($kunci, ['A','B','C','D'])) {
            $errors[] = "Baris $rowNumber: kunci harus A, B, C, atau D";
            continue;
        }

        if ($poin <= 0) {
            $errors[] = "Baris $rowNumber: poin harus > 0";
            continue;
        }

        if ($wm && !strtotime($wm)) {
            $errors[] = "Baris $rowNumber: format waktu_mulai tidak valid";
            continue;
        }
        if ($ws && !strtotime($ws)) {
            $errors[] = "Baris $rowNumber: format waktu_selesai tidak valid";
            continue;
        }

        $no_soal++;
        $gambar = $gambarMap[$rowNumber] ?? null;

        // 🔥 INSERT dengan opsi A-D saja
        $stmt = $conn->prepare("
            INSERT INTO seleksi
            (id_admin, id_jurusan, no_soal, soal, opsi_a, opsi_b, opsi_c, opsi_d,
             kunci_jawaban, poin, waktu_mulai, waktu_selesai, gambar_soal)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->bind_param(
            "ssissssssisss",
            $id_admin,
            $id_jurusan,
            $no_soal,
            $soal,
            $opsiA,
            $opsiB,
            $opsiC,
            $opsiD,
            $kunci,
            $poin,
            $wm,
            $ws,
            $gambar
        );

        if ($stmt->execute()) {
            $inserted++;
        } else {
            $errors[] = "Baris $rowNumber gagal: " . $stmt->error;
        }
        $stmt->close();
    }

    echo json_encode([
        'status'   => 'success',
        'inserted' => $inserted,
        'errors'   => $errors
    ]);

} catch (Exception $e) {
    echo json_encode([
        'status'  => 'error',
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>