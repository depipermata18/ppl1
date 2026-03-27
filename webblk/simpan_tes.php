<?php
session_start();
include 'config/database.php';

// ======================================
// CEK LOGIN
// ======================================
if (!isset($_SESSION['nik'])) {
    header("Location: login.php");
    exit;
}

$nik = $_SESSION['nik'];

// ======================================
// AMBIL DATA PESERTA
// ======================================
$stmt = $conn->prepare("SELECT id_peserta, id_jurusan, id_admin FROM peserta WHERE NIK = ?");
$stmt->bind_param("s", $nik);
$stmt->execute();
$peserta = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$peserta) {
    die("Data peserta tidak ditemukan!");
}

$id_peserta  = (int)$peserta['id_peserta'];
$id_jurusan  = (int)$peserta['id_jurusan'];
$id_admin_final = $peserta['id_admin']; // admin yang menaungi peserta

// ======================================
// VALIDASI JAWABAN
// ======================================
if (!isset($_POST['jawaban'])) {
    die("Tidak ada jawaban terkirim!");
}

$jawaban_user = $_POST['jawaban']; // bentuk array: id_soal => jawaban

// ======================================
// AMBIL SELURUH SOAL (sesuai jurusan peserta)
// ======================================
$stmt = $conn->prepare("
    SELECT id_seleksi, kunci_jawaban, poin 
    FROM seleksi 
    WHERE id_jurusan = ?
");
$stmt->bind_param("i", $id_jurusan);
$stmt->execute();
$soal = $stmt->get_result();
$stmt->close();

$total_benar = 0;
$total_salah = 0;
$total_poin  = 0;

// ======================================
// HITUNG NILAI
// ======================================
while ($row = $soal->fetch_assoc()) {

    $id_soal = $row['id_seleksi'];
    $kunci   = strtolower(trim($row['kunci_jawaban']));
    $poin    = (int)$row['poin'];

    // Ambil jawaban user
    $jawab = isset($jawaban_user[$id_soal]) ? strtolower($jawaban_user[$id_soal]) : '';

    if ($jawab === $kunci) {
        $total_benar++;
        $total_poin += $poin;
    } else {
        $total_salah++;
    }
}

// ======================================
// SIMPAN KE TABEL LAPORAN
// ======================================
$tanggal = date("Y-m-d");

$stmt = $conn->prepare("
    INSERT INTO laporan (tanggal_tes, total_poin, total_benar, total_salah, id_admin, id_peserta)
    VALUES (?, ?, ?, ?, ?, ?)
");

$stmt->bind_param(
    "siiiis",
    $tanggal,
    $total_poin,
    $total_benar,
    $total_salah,
    $id_admin_final,
    $id_peserta
);

if ($stmt->execute()) {

    echo "<script>
        alert('Tes berhasil disimpan!');
        window.location='riwayat.php';
    </script>";

} else {
    echo "<h3>Error saat menyimpan: {$stmt->error}</h3>";
}

$stmt->close();
$conn->close();
?>
