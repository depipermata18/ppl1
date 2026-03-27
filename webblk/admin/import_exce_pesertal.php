<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include '../config/database.php'; 
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

header('Content-Type: application/json');

// Cek file Excel
if (!isset($_FILES['file_excel']) || empty($_FILES['file_excel']['tmp_name'])) {
    echo json_encode(['status' => 'error', 'message' => 'File tidak ditemukan']);
    exit;
}

$allowedExtensions = ['xls', 'xlsx'];
$filename = $_FILES['file_excel']['name'];
$ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

if (!in_array($ext, $allowedExtensions)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Hanya file Excel (.xls atau .xlsx) yang diperbolehkan'
    ]);
    exit;
}

$file = $_FILES['file_excel']['tmp_name'];
$inserted = 0;
$errors = [];

try {
    $spreadsheet = IOFactory::load($file);
    $sheet = $spreadsheet->getActiveSheet();
    $rows = $sheet->toArray(null, true, true, true);

    $rowNumber = 0;

    foreach ($rows as $row) {
        $rowNumber++;
        if ($rowNumber == 1) continue; // skip header

        $nama          = trim($row['A'] ?? '');
        $alamat        = trim($row['B'] ?? '');
        $jenis_kelamin = trim($row['C'] ?? '');
        $tgl_lahir     = trim($row['D'] ?? '');
        $NIK           = trim($row['E'] ?? '');
        $password      = trim($row['F'] ?? '');
        $NO_HP         = trim($row['G'] ?? '');
        $email         = trim($row['H'] ?? '');
        $id_jurusan    = (int)($row['I'] ?? 0);
        $id_admin      = $_SESSION['id_admin'] ?? 1;

        // Validasi wajib
        if (!$nama || !$NIK) {
            $errors[] = "Baris $rowNumber di-skip: nama atau NIK kosong";
            continue;
        }

        // Validasi NIK 16 digit
        if (strlen($NIK) !== 16 || !ctype_digit($NIK)) {
            $errors[] = "Baris $rowNumber di-skip: NIK harus 16 digit angka";
            continue;
        }

        // Cek duplikat NIK
        $checkStmt = $conn->prepare("SELECT id_peserta FROM peserta WHERE NIK = ?");
        $checkStmt->bind_param("s", $NIK);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        if ($checkResult && $checkResult->num_rows > 0) {
            $errors[] = "Baris $rowNumber di-skip: NIK '$NIK' sudah ada";
            continue;
        }

        // Cek jurusan
        $checkJurusan = $conn->prepare("SELECT id_jurusan FROM jurusan WHERE id_jurusan = ?");
        $checkJurusan->bind_param("i", $id_jurusan);
        $checkJurusan->execute();
        $resJurusan = $checkJurusan->get_result();
        if ($resJurusan->num_rows == 0) {
            $errors[] = "Baris $rowNumber di-skip: id_jurusan tidak ditemukan";
            continue;
        }

        // Hash password
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        // Insert data
        $stmt = $conn->prepare("
            INSERT INTO peserta 
            (nama_peserta, alamat, jenis_kelamin, tgl_lahir, NIK, password, NO_HP, email, id_admin, id_jurusan)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param(
            "ssssssssii",
            $nama, $alamat, $jenis_kelamin, $tgl_lahir, $NIK,
            $passwordHash, $NO_HP, $email, $id_admin, $id_jurusan
        );

        if ($stmt->execute()) {
            $inserted++;
        } else {
            $errors[] = "Baris $rowNumber gagal: " . $stmt->error;
        }
    }

  echo json_encode([
    "status" => "success", 
    "inserted" => $inserted,
    "errors" => $errors 
]);


} catch (Exception $e) {
    echo json_encode([
        "status" => "error",
        "message" => "Gagal membaca file: " . $e->getMessage()
    ]);
}
?>
