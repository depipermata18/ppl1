<?php
header('Content-Type: application/json');
session_start();

// =======================================================
// CEK LOGIN ADMIN
// =======================================================
if (
    !isset($_SESSION['id_admin']) ||
    !isset($_SESSION['role']) ||
    $_SESSION['role'] !== 'admin'
) {
    echo json_encode([
        'logged_in_admin_name' => 'Akun',
        'logged_in_username' => '',
        'logged_in_foto_profil_path' => '../images/profile.png',
        "html" => "<div style='padding:40px;text-align:center;color:red;font-weight:bold'>
                      Anda harus login terlebih dahulu.
                   </div>",
        "redirect" => "../login.php"
    ]);
    exit;
}

// =======================================================
// AMBIL KONEKSI DATABASE
// =======================================================
ob_start();
include '../config/database.php';
$output_buffer = ob_get_clean();

if ($output_buffer !== '') {
    echo json_encode([
        'error' => 'Output tidak valid dari config/database.php'
    ]);
    exit;
}

$id_admin = intval($_SESSION['id_admin']);

// DEFAULT VALUE
$logged_in_admin_name = 'Akun';
$logged_in_username = '';
$logged_in_foto_profil_path = '../images/profile.png';

// =======================================================
// AMBIL PROFIL ADMIN
// =======================================================
$stmt = $conn->prepare("
    SELECT nama_admin, username, foto_profil
    FROM admin
    WHERE id_admin = ?
");

if ($stmt) {
    $stmt->bind_param("i", $id_admin);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {

        $logged_in_admin_name = $row['nama_admin'] ?: 'Akun';
        $logged_in_username = $row['username'] ?: '';

        if (!empty($row['foto_profil'])) {
            $logged_in_foto_profil_path = '../images/' . $row['foto_profil'];
        }
    }
    $stmt->close();
}

// === MODE PDF: LAPORAN LENGKAP ===
if (isset($_GET['download']) && $_GET['download'] === 'pdf') {
    // Karena vendor berada di: webblk/admin/vendor/
    $autoload = __DIR__ . '/vendor/autoload.php';
    $manual = __DIR__ . '/vendor/dompdf/dompdf/src/Autoloader.php';

    if (file_exists($autoload)) {
        require_once $autoload;
    } elseif (file_exists($manual)) {
        require_once $manual;
        \Dompdf\Autoloader::register();
    } else {
        http_response_code(500);
        echo json_encode(array_merge([
            'logged_in_admin_name' => $logged_in_admin_name,
            'logged_in_username' => $logged_in_username,
            'logged_in_foto_profil_path' => $logged_in_foto_profil_path
        ], [
            'error' => 'DOMPDF tidak ditemukan. Pastikan folder vendor/dompdf/dompdf ada di: ' . __DIR__ . '/vendor/'
        ]));
        exit;
    }

    try {
        // Ambil data peserta
        $sql_peserta_pdf = "
            SELECT 
                p.id_peserta,
                p.nama_peserta,
                p.NIK,
                p.alamat,
                j.nama_jurusan,
                p.status,
                p.tgl_daftar
            FROM peserta p
            LEFT JOIN jurusan j ON p.id_jurusan = j.id_jurusan
            ORDER BY p.tgl_daftar DESC
        ";
        $result_peserta_pdf = $conn->query($sql_peserta_pdf);
        $peserta_pdf = $result_peserta_pdf ? $result_peserta_pdf->fetch_all(MYSQLI_ASSOC) : [];

        // Ambil data status
        $sql_status_pdf = "
            SELECT 
                SUM(CASE WHEN status = 'aktif' THEN 1 ELSE 0 END) AS aktif,
                SUM(CASE WHEN status = 'drop_out' THEN 1 ELSE 0 END) AS drop_out,
                SUM(CASE WHEN status = 'seleksi' THEN 1 ELSE 0 END) AS seleksi,
                SUM(CASE WHEN status = 'lulus' THEN 1 ELSE 0 END) AS lulus
            FROM peserta
        ";
        $result_status_pdf = $conn->query($sql_status_pdf);
        $status_data_pdf = $result_status_pdf ? $result_status_pdf->fetch_assoc() : ['aktif' => 0, 'drop_out' => 0, 'seleksi' => 0, 'lulus' => 0];

        // Ambil data jadwal
        $sql_jadwal_pdf = "
            SELECT 
                j.nama_jadwal,
                j.keterangan,
                j.waktu_mulai,
                jur.nama_jurusan
            FROM jadwal j
            INNER JOIN jurusan jur ON j.id_jurusan = jur.id_jurusan
            ORDER BY j.waktu_mulai DESC
        ";
        $result_jadwal_pdf = $conn->query($sql_jadwal_pdf);
        $jadwal_pdf = $result_jadwal_pdf ? $result_jadwal_pdf->fetch_all(MYSQLI_ASSOC) : [];

        // === BACA LOGO SEBAGAI BASE64 (PASTI MUNCUL DI PDF) ===
        $logo_file = __DIR__ . '/images/profile.png';
        $logo_base64 = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII='; // fallback: 1x1 transparan
        if (file_exists($logo_file)) {
            $logo_data = file_get_contents($logo_file);
            if ($logo_data !== false) {
                $logo_base64 = 'data:image/png;base64,' . base64_encode($logo_data);
            }
        }

        // === HTML PDF DENGAN KOP SURAT RESMI ===
        $html = '<!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; margin: 30px; color: #000; }
                .kop-surat { display: flex; align-items: center; border-bottom: 3px solid #3565A5; padding-bottom: 15px; margin-bottom: 25px; }
                .kop-surat img { height: 65px; margin-right: 20px; }
                .kop-text { font-size: 13px; }
                .kop-text .instansi-utama { font-weight: bold; font-size: 16px; color: #3565A5; line-height: 1.3; }
                .kop-text .instansi-sekunder { font-weight: bold; font-size: 15px; margin-top: 3px; line-height: 1.3; }
                .kop-text .alamat { margin-top: 4px; color: #333; }
                .header-laporan { text-align: center; margin: 15px 0 25px; }
                .header-laporan h1 { color: #3565A5; font-size: 18px; margin: 8px 0; }
                .content { margin-top: 10px; }
                .section { margin-bottom: 30px; }
                .section h2 { color: #3565A5; border-bottom: 2px solid #3565A5; padding-bottom: 5px; margin: 20px 0 15px; font-size: 16px; }
                table { width: 100%; border-collapse: collapse; margin-top: 10px; }
                th, td { border: 1px solid #999; padding: 8px; text-align: left; }
                th { background-color: #f0f9ff; font-weight: bold; }
                .stats { display: flex; gap: 15px; flex-wrap: wrap; margin: 15px 0; }
                .stat-box { padding: 10px 15px; border: 1px solid #ccc; border-radius: 5px; text-align: center; background: #fafafa; min-width: 90px; }
                .stat-value { font-size: 18px; font-weight: bold; color: #3565A5; margin-bottom: 4px; }
                .footer-ttd { margin-top: 60px; text-align: right; padding-right: 40px; }
            </style>
        </head>
        <body>

            <!-- KOP SURAT RESMI -->
            <div class="kop-surat">
                <img src="' . $logo_base64 . '" alt="Logo UPT BLK Nganjuk">
                <div class="kop-text">
                    <div class="instansi-utama">DINAS TENAGA KERJA DAN TRANSMIGRASI</div>
                    <div class="instansi-utama">PROVINSI JAWA TIMUR</div>
                    <div class="instansi-sekunder">UNIT PELAKSANA TEKNIS BALAI LATIHAN KERJA (UPT BLK) NGANJUK</div>
                    <div class="alamat">Jalan Veteran No. 87, Nganjuk, Jawa Timur</div>
                    <div class="alamat">Telp. (0358) XXXXXXX | Email: blk.nganjuk@jatimprov.go.id</div>
                </div>
            </div>

            <div class="header-laporan">
                <h1>LAPORAN KEADAAN PESERTA DAN JADWAL PELATIHAN</h1>
                <p>Periode: ' . date('F Y') . '</p>
            </div>
            
            <div class="content">
                <div class="section">
                    <h2>📊 KETERANGAN STATUS PESERTA</h2>
                    <div class="stats">
                        <div class="stat-box">
                            <div class="stat-value">' . (int)($status_data_pdf['aktif'] ?? 0) . '</div>
                            <div>Aktif</div>
                        </div>
                        <div class="stat-box">
                            <div class="stat-value">' . (int)($status_data_pdf['seleksi'] ?? 0) . '</div>
                            <div>Seleksi</div>
                        </div>
                        <div class="stat-box">
                            <div class="stat-value">' . (int)($status_data_pdf['lulus'] ?? 0) . '</div>
                            <div>Lulus</div>
                        </div>
                        <div class="stat-box">
                            <div class="stat-value">' . (int)($status_data_pdf['drop_out'] ?? 0) . '</div>
                            <div>Drop Out</div>
                        </div>
                    </div>
                </div>

                <div class="section">
                    <h2>👥 Data Semua Peserta</h2>
                    <table>
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama</th>
                                <th>NIK</th>
                                <th>Alamat</th>
                                <th>Jurusan</th>
                                <th>Status</th>
                                <th>Tgl Daftar</th>
                            </tr>
                        </thead>
                        <tbody>';

        $no = 1;
        foreach ($peserta_pdf as $p) {
            $html .= '
                            <tr>
                                <td>' . $no++ . '</td>
                                <td>' . htmlspecialchars($p['nama_peserta'] ?? '—') . '</td>
                                <td>' . htmlspecialchars($p['NIK'] ?? '—') . '</td>
                                <td>' . htmlspecialchars($p['alamat'] ?? '—') . '</td>
                                <td>' . htmlspecialchars($p['nama_jurusan'] ?? '—') . '</td>
                                <td>' . ucfirst(str_replace('_', ' ', $p['status'] ?? '')) . '</td>
                                <td>' . ($p['tgl_daftar'] ? date('d/m/Y', strtotime($p['tgl_daftar'])) : '—') . '</td>
                            </tr>';
        }

        $html .= '
                        </tbody>
                    </table>
                </div>

                <div class="section">
                    <h2>📅 Jadwal Terbaru</h2>
                    <table>
                        <thead>
                            <tr>
                                <th>Nama Jadwal</th>
                                <th>Keterangan</th>
                                <th>Jurusan</th>
                                <th>Tanggal</th>
                            </tr>
                        </thead>
                        <tbody>';

        foreach ($jadwal_pdf as $j) {
            $html .= '
                            <tr>
                                <td>' . htmlspecialchars($j['nama_jadwal'] ?? '—') . '</td>
                                <td>' . htmlspecialchars($j['keterangan'] ?? '—') . '</td>
                                <td>' . htmlspecialchars($j['nama_jurusan'] ?? '—') . '</td>
                                <td>' . ($j['waktu_mulai'] ? date('d/m/Y H:i', strtotime($j['waktu_mulai'])) : '—') . '</td>
                            </tr>';
        }

        $html .= '
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="footer-ttd">
                <p>Nganjuk, ' . date('d F Y') . '</p>
                <p style="margin-top: 40px;"><strong>KEPALA UPT BLK NGANJUK</strong></p>
            </div>
        </body>
        </html>';

        // Render PDF
        $options = new \Dompdf\Options();
        $options->set('defaultFont', 'Arial');
        $options->setIsRemoteEnabled(false);
        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        // Kirim ke browser
        $filename = 'laporan_dashboard_blk_' . date('Y-m-d') . '.pdf';
        $dompdf->stream($filename, ['Attachment' => false]);
        exit;

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(array_merge([
            'logged_in_admin_name' => $logged_in_admin_name,
            'logged_in_username' => $logged_in_username,
            'logged_in_foto_profil_path' => $logged_in_foto_profil_path
        ], [
            'error' => 'Gagal menghasilkan PDF: ' . $e->getMessage()
        ]));
        exit;
    }
}

// === MODE PDF: JADWAL PELATIHAN (LEMBAR TERPISAH) ===
if (isset($_GET['download']) && $_GET['download'] === 'schedule_pdf') {
    // Load DOMPDF
    $autoload = __DIR__ . '/vendor/autoload.php';
    $manual = __DIR__ . '/vendor/dompdf/dompdf/src/Autoloader.php';
    if (file_exists($autoload)) {
        require_once $autoload;
    } elseif (file_exists($manual)) {
        require_once $manual;
        \Dompdf\Autoloader::register();
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'DOMPDF tidak ditemukan.']);
        exit;
    }

    try {
        // Ambil data jadwal
        $sql = "
            SELECT 
                j.nama_jadwal,
                j.keterangan,
                j.waktu_mulai,
                j.waktu_selesai,
                jur.nama_jurusan
            FROM jadwal j
            INNER JOIN jurusan jur ON j.id_jurusan = jur.id_jurusan
            ORDER BY j.waktu_mulai DESC
        ";
        $result = $conn->query($sql);
        $jadwal_list = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

        // === BACA LOGO SEBAGAI BASE64 ===
        $logo_file = __DIR__ . '/images/profile.png';
        $logo_base64 = 'image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=';
        if (file_exists($logo_file)) {
            $logo_data = file_get_contents($logo_file);
            if ($logo_data !== false) {
                $logo_base64 = 'image/png;base64,' . base64_encode($logo_data);
            }
        }

        // === BUAT HTML PDF ===
        $html = '<!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; margin: 30px; color: #000; }
                .kop-surat {
                    text-align: center;
                    border-bottom: 3px solid #3565A5;
                    padding-bottom: 15px;
                    margin-bottom: 25px;
                }
                .kop-surat img {
                    height: 65px;
                    margin-bottom: 10px;
                }
                .kop-text .instansi-utama {
                    font-weight: bold;
                    font-size: 16px;
                    color: #3565A5;
                    line-height: 1.3;
                }
                .kop-text .instansi-sekunder {
                    font-weight: bold;
                    font-size: 15px;
                    margin: 6px 0;
                    line-height: 1.3;
                }
                .kop-text .alamat {
                    font-size: 13px;
                    color: #333;
                    margin-top: 4px;
                }
                .header {
                    text-align: center;
                    margin: 20px 0 30px;
                }
                .header h1 {
                    color: #3565A5;
                    font-size: 18px;
                    margin: 10px 0;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-top: 10px;
                }
                th, td {
                    border: 1px solid #999;
                    padding: 10px;
                    text-align: left;
                }
                th {
                    background-color: #f0f9ff;
                    font-weight: bold;
                }
                .footer-ttd {
                    margin-top: 60px;
                    text-align: right;
                    padding-right: 40px;
                }
            </style>
        </head>
        <body>

            <!-- KOP SURAT (TEKS DI TENGAH) -->
            <div class="kop-surat">
                <img src="' . $logo_base64 . '" alt="Logo UPT BLK Nganjuk">
                <div class="kop-text">
                    <div class="instansi-utama">.              DINAS TENAGA KERJA DAN TRANSMIGRASI</div>
                    <div class="instansi-utama">                     PROVINSI JAWA TIMUR</div>
                    <div class="instansi-sekunder">UNIT PELAKSANA TEKNIS BALAI LATIHAN KERJA (UPT BLK) NGANJUK</div>
                    <div class="alamat">                     Jalan Veteran No. 87, Nganjuk, Jawa Timur</div>
                    <div class="alamat">              Telp. (0358) XXXXXXX | Email: blk.nganjuk@jatimprov.go.id</div>
                </div>
            </div>

            <div class="header">
                <h1>LEMBAR JADWAL PELATIHAN</h1>
                <p>Periode: ' . date('F Y') . '</p>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama Jadwal</th>
                        <th>Jurusan</th>
                        <th>Tanggal Mulai</th>
                        <th>Tanggal Selesai</th>
                        <th>Keterangan</th>
                    </tr>
                </thead>
                <tbody>';

        $no = 1;
        foreach ($jadwal_list as $j) {
            $mulai = $j['waktu_mulai'] ? date('d/m/Y H:i', strtotime($j['waktu_mulai'])) : '—';
            $selesai = $j['waktu_selesai'] ? date('d/m/Y H:i', strtotime($j['waktu_selesai'])) : '—';
            $html .= '
                    <tr>
                        <td>' . $no++ . '</td>
                        <td>' . htmlspecialchars($j['nama_jadwal']) . '</td>
                        <td>' . htmlspecialchars($j['nama_jurusan']) . '</td>
                        <td>' . $mulai . '</td>
                        <td>' . $selesai . '</td>
                        <td>' . htmlspecialchars($j['keterangan'] ?? '—') . '</td>
                    </tr>';
        }

        $html .= '
                </tbody>
            </table>

            <div class="footer-ttd">
                <p>Nganjuk, ' . date('d F Y') . '</p>
                <p style="margin-top: 40px;"><strong>KEPALA UPT BLK NGANJUK</strong></p>
            </div>

        </body>
        </html>';

        // Render PDF
        $options = new \Dompdf\Options();
        $options->set('defaultFont', 'Arial');
        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $dompdf->stream('jadwal_pelatihan.pdf', ['Attachment' => false]);
        exit;

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(array_merge([
            'logged_in_admin_name' => $logged_in_admin_name ?? 'Admin',
            'logged_in_username' => $logged_in_username ?? '',
            'logged_in_foto_profil_path' => $logged_in_foto_profil_path ?? '../images/profile.png'
        ], [
            'error' => 'Gagal menghasilkan PDF jadwal: ' . $e->getMessage()
        ]));
        exit;
    }
}

// =======================================================
// MODE KOREKSI JAWABAN — HANYA UNTUK JURUSAN ADMIN YANG LOGIN
// =======================================================
if (isset($_GET['mode']) && $_GET['mode'] === 'koreksi') {
    // Cek session admin
    if (
        !isset($_SESSION['id_admin']) ||
        !isset($_SESSION['role']) ||
        $_SESSION['role'] !== 'admin'
    ) {
        echo json_encode(['error' => 'Akses ditolak']);
        exit;
    }

    // Ambil id_jurusan dari session admin
    $id_jurusan_admin = $_SESSION['id_jurusan'] ?? null;
    if (!$id_jurusan_admin) {
        echo json_encode(['error' => 'Admin tidak memiliki jurusan terkait']);
        exit;
    }

    // Detail per peserta
    if (isset($_GET['id_peserta'])) {
        $id_peserta = intval($_GET['id_peserta']);
        
        // Validasi: pastikan peserta milik jurusan admin
        $stmt_check = $conn->prepare("
            SELECT p.id_peserta 
            FROM peserta p 
            WHERE p.id_peserta = ? AND p.id_jurusan = ?
        ");
        $stmt_check->bind_param("ii", $id_peserta, $id_jurusan_admin);
        $stmt_check->execute();
        if ($stmt_check->get_result()->num_rows === 0) {
            echo json_encode(['error' => 'Peserta tidak ditemukan atau bukan milik jurusan Anda']);
            exit;
        }
        $stmt_check->close();

        // Ambil detail jawaban
        $sql_detail = "
            SELECT 
                s.no_soal, s.soal, s.opsi_a, s.opsi_b, s.opsi_c, s.opsi_d, s.opsi_e,
                s.kunci_jawaban, dl.jawaban, dl.benar_salah, dl.waktu_jawab
            FROM detail_laporan dl
            JOIN seleksi s ON dl.id_seleksi = s.id_seleksi
            JOIN laporan l ON dl.id_laporan = l.id_laporan
            WHERE l.id_peserta = ?
            ORDER BY s.no_soal ASC
        ";
        $stmt_detail = $conn->prepare($sql_detail);
        $stmt_detail->bind_param("i", $id_peserta);
        $stmt_detail->execute();
        $detail = $stmt_detail->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt_detail->close();

        // Ambil info peserta
        $sql_info = "
            SELECT p.nama_peserta, j.nama_jurusan, l.total_poin, l.total_benar, l.total_salah, l.tanggal_tes
            FROM peserta p
            JOIN jurusan j ON p.id_jurusan = j.id_jurusan
            JOIN laporan l ON p.id_peserta = l.id_peserta
            WHERE p.id_peserta = ?
        ";
        $stmt_info = $conn->prepare($sql_info);
        $stmt_info->bind_param("i", $id_peserta);
        $stmt_info->execute();
        $info = $stmt_info->get_result()->fetch_assoc();
        $stmt_info->close();

        // Format respons
        echo json_encode([
            'success' => true,
            'type' => 'detail',
            'peserta' => $info,
            'jawaban' => array_map(function($row) {
                return [
                    'no_soal' => (int)$row['no_soal'],
                    'soal' => $row['soal'],
                    'opsi' => [
                        'A' => $row['opsi_a'],
                        'B' => $row['opsi_b'],
                        'C' => $row['opsi_c'],
                        'D' => $row['opsi_d'],
                        'E' => $row['opsi_e'],
                    ],
                    'kunci' => $row['kunci_jawaban'],
                    'jawaban' => $row['jawaban'],
                    'benar_salah' => (bool)$row['benar_salah'],
                    'waktu_jawab' => $row['waktu_jawab'] ? date('d/m H:i', strtotime($row['waktu_jawab'])) : '—'
                ];
            }, $detail)
        ]);
        exit;
    }

    // Daftar peserta untuk koreksi
    $sql_list = "
        SELECT 
            p.id_peserta,
            p.nama_peserta,
            p.NIK,
            j.nama_jurusan,
            l.id_laporan,
            l.total_poin,
            l.total_benar,
            l.total_salah,
            l.tanggal_tes
        FROM peserta p
        INNER JOIN jurusan j ON p.id_jurusan = j.id_jurusan
        INNER JOIN laporan l ON p.id_peserta = l.id_peserta
        WHERE p.status = 'seleksi' 
          AND p.id_jurusan = ?
        ORDER BY l.tanggal_tes DESC
    ";
    $stmt_list = $conn->prepare($sql_list);
    $stmt_list->bind_param("i", $id_jurusan_admin);
    $stmt_list->execute();
    $result_list = $stmt_list->get_result();
    $peserta_koreksi = [];
    
    while ($row = $result_list->fetch_assoc()) {
        $peserta_koreksi[] = [
            'id_peserta' => (int)$row['id_peserta'],
            'nama_peserta' => $row['nama_peserta'],
            'NIK' => $row['NIK'],
            'nama_jurusan' => $row['nama_jurusan'],
            'id_laporan' => (int)$row['id_laporan'],
            'total_poin' => (int)($row['total_poin'] ?? 0),
            'total_benar' => (int)($row['total_benar'] ?? 0),
            'total_salah' => (int)($row['total_salah'] ?? 0),
            'tanggal_tes' => $row['tanggal_tes'] ? date('d/m/Y H:i', strtotime($row['tanggal_tes'])) : '—'
        ];
    }
    $stmt_list->close();

    echo json_encode([
        'success' => true,
        'type' => 'list',
        'peserta_koreksi' => $peserta_koreksi,
        'count' => count($peserta_koreksi)
    ]);
    exit;
}

// =======================================================
// QUERY DASHBOARD (DEFAULT)
// =======================================================

// 1. DATA JURUSAN
$jurusan_chart = [];
$sql = "
    SELECT j.id_jurusan, j.nama_jurusan, COUNT(p.id_peserta) AS jumlah
    FROM jurusan j
    LEFT JOIN peserta p ON j.id_jurusan = p.id_jurusan
    GROUP BY j.id_jurusan, j.nama_jurusan
    ORDER BY j.nama_jurusan
";
$res = $conn->query($sql);
if ($res) $jurusan_chart = $res->fetch_all(MYSQLI_ASSOC);

// 2. STATUS PESERTA
$status_data = [
    'aktif' => 0, 'drop_out' => 0, 'seleksi' => 0, 'lulus' => 0
];

$sql = "
    SELECT 
        SUM(CASE WHEN status = 'aktif' THEN 1 ELSE 0 END) AS aktif,
        SUM(CASE WHEN status = 'drop_out' THEN 1 ELSE 0 END) AS drop_out,
        SUM(CASE WHEN status = 'seleksi' THEN 1 ELSE 0 END) AS seleksi,
        SUM(CASE WHEN status = 'lulus' THEN 1 ELSE 0 END) AS lulus
    FROM peserta
";
$res = $conn->query($sql);
if ($res) $status_data = $res->fetch_assoc();

// 3. JADWAL BULAN INI
$jadwal_per_jurusan = [];
$bulan = date('m');
$tahun = date('Y');

$stmt = $conn->prepare("
    SELECT 
        j.nama_jadwal,
        j.keterangan,
        j.waktu_mulai,
        jur.nama_jurusan
    FROM jadwal j
    INNER JOIN jurusan jur ON j.id_jurusan = jur.id_jurusan
    WHERE MONTH(j.waktu_mulai) = ?
      AND YEAR(j.waktu_mulai) = ?
    ORDER BY j.waktu_mulai
");
if ($stmt) {
    $stmt->bind_param("ss", $bulan, $tahun);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $jadwal_per_jurusan[] = $row;
    }
    $stmt->close();
}

// 4. DAFTAR PESERTA
$sql = "
    SELECT p.id_peserta, p.nama_peserta, j.nama_jurusan, p.status
    FROM peserta p
    LEFT JOIN jurusan j ON p.id_jurusan = j.id_jurusan
    WHERE p.status IN ('aktif', 'seleksi', 'lulus')
    ORDER BY FIELD(p.status, 'seleksi', 'aktif', 'lulus'), p.nama_peserta
";
$all_peserta = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);

// 5. LIST NAMA JURUSAN
$jurusan_list = [];
$res = $conn->query("SELECT nama_jurusan FROM jurusan ORDER BY nama_jurusan");
while ($row = $res->fetch_assoc()) {
    $jurusan_list[] = $row['nama_jurusan'];
}

// =======================================================
// KIRIM JSON RESPON
// =======================================================
echo json_encode([
    'logged_in_admin_name' => $logged_in_admin_name,
    'logged_in_username' => $logged_in_username,
    'logged_in_foto_profil_path' => $logged_in_foto_profil_path,
    'jurusan_chart' => $jurusan_chart,
    'status_data' => $status_data,
    'jadwal_per_jurusan' => $jadwal_per_jurusan,
    'all_peserta' => $all_peserta,
    'jurusan_list' => $jurusan_list,
    'tahun' => intval(date('Y'))
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);