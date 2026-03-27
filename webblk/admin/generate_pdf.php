<?php
session_start();
// Cek otorisasi
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    exit('Akses ditolak');
}

include '../config/database.php'; 
require_once '/vendor/dompdf.php'; // Pastikan DOMPDF terinstal

use Dompdf\Dompdf;
use Dompdf\Options;

$options = new Options();
$options->set('defaultFont', 'Poppins');
$options->set('isRemoteEnabled', true);
$dompdf = new Dompdf($options);

$type = $_GET['type'] ?? 'all';
$jurusan = $_GET['jurusan'] ?? null;

// Ambil data
$where = "1=1";
$params = [];
$types = "s";

if ($type === 'current' && $jurusan && $jurusan !== 'all') {
    $where .= " AND j.nama_jurusan = ?";
    $params[] = $jurusan;
    $types = "s"; 
}

$sql = "
    SELECT p.*, j.nama_jurusan 
    FROM peserta p
    LEFT JOIN jurusan j ON p.id_jurusan = j.id_jurusan
    WHERE $where
    ORDER BY p.tgl_daftar DESC
";
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$peserta = $result->fetch_all(MYSQLI_ASSOC);

// Format judul
$judul = $type === 'all' ? 'Semua Peserta' : "Peserta Jurusan $jurusan";

// Bangun HTML untuk PDF
$html = '
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <style>
    body { font-family: Arial, sans-serif; margin: 40px; }
    .header { text-align: center; margin-bottom: 30px; }
    .logo { width: 80px; margin-bottom: 10px; }
    h1 { color: #3565A5; margin: 10px 0; }
    .kop-surat { margin-bottom: 30px; }
    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
    th, td { border: 1px solid #333; padding: 10px; text-align: left; }
    th { background-color: #f2f2f2; }
    .footer { margin-top: 40px; text-align: right; }
  </style>
</head>
<body>
  <div class="header">
    <img src="' . $_SERVER['DOCUMENT_ROOT'] . '/images/logo.png" class="logo" alt="Logo">
    <h2>PEMERINTAH KABUPATEN NGANJUK</h2>
    <h1>UPT BALAI LATIHAN KERJA (BLK) NGANJUK</h1>
    <p>Jalan Veteran No. 87, Nganjuk, Jawa Timur</p>
  </div>

  <div class="kop-surat">
    <h3>LAPORAN DATA PESERTA</h3>
    <p><strong>Jenis Laporan:</strong> ' . $judul . '</p>
    <p><strong>Tanggal Cetak:</strong> ' . date('d F Y') . '</p>
  </div>

  <table>
    <thead>
      <tr>
        <th>No</th>
        <th>Nama</th>
        <th>NIK</th>
        <th>Jurusan</th>
        <th>Status</th>
        <th>Tanggal Daftar</th>
      </tr>
    </thead>
    <tbody>';

$no = 1;
foreach ($peserta as $p) {
    $html .= '<tr>
        <td>' . $no++ . '</td>
        <td>' . htmlspecialchars($p['nama_peserta']) . '</td>
        <td>' . htmlspecialchars($p['NIK']) . '</td>
        <td>' . htmlspecialchars($p['nama_jurusan'] ?? '—') . '</td>
        <td>' . ucfirst(str_replace('_', ' ', $p['status'])) . '</td>
        <td>' . date('d/m/Y', strtotime($p['tgl_daftar'])) . '</td>
    </tr>';
}

$html .= '
    </tbody>
  </table>

  <div class="footer">
    <p>Nganjuk, ' . date('d F Y') . '</p>
    <p style="margin-top: 40px;"><strong>Kepala UPT BLK Nganjuk</strong></p>
  </div>
</body>
</html>';

$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// Output ke browser
$filename = 'laporan_peserta_' . ($type === 'all' ? 'semua' : 'jurusan_' . str_replace(' ', '_', $jurusan)) . '.pdf';
$dompdf->stream($filename, ["Attachment" => false]);
?>