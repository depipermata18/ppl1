<?php
// ===================================================
// data_peserta_api.php — FINAL FIX + RANKING + GET_JURUSAN + FILTER JURUSAN DI KOREKSI
// DIPERBARUI: Tambahkan mode=get_jurusan & filter jurusan di mode koreksi
// ===================================================

ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

/** ====== Helpers ====== **/

function json_exit($data, $code = 200) {
    if (ob_get_length()) @ob_clean();
    if (!headers_sent()) header('Content-Type: application/json; charset=utf-8');
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function get_db_connection() {
    $candidates = [
        __DIR__ . '/../config/database.php',
        __DIR__ . '/../../config/database.php',
    ];
    foreach ($candidates as $p) {
        if (file_exists($p)) {
            $conn = require $p;
            if ($conn instanceof mysqli) return $conn;
            if (isset($GLOBALS['conn']) && $GLOBALS['conn'] instanceof mysqli) return $GLOBALS['conn'];
        }
    }
    return null;
}

function load_dompdf_autoload() : bool {
    $candidates = [
        __DIR__ . '/vendor/autoload.php',
        __DIR__ . '/../vendor/autoload.php',
        __DIR__ . '/../../vendor/autoload.php',
    ];
    foreach ($candidates as $file) {
        if (file_exists($file)) {
            require_once $file;
            return true;
        }
    }
    return false;
}

function get_kop_surat_html() {
    $logo_file = __DIR__ . '/images/profile.png';
    $logo_base64 = 'image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=';
    if (file_exists($logo_file)) {
        $logo_data = @file_get_contents($logo_file);
        if ($logo_data !== false) {
            $logo_base64 = 'image/png;base64,' . base64_encode($logo_data);
        }
    }

    return '
    <table width="100%" style="border-collapse: collapse; margin-bottom: 20px;">
        <tr>
            <td width="120" style="padding-right: 20px; vertical-align: top;">
                <img src="' . $logo_base64 . '" alt="Logo UPT BLK Nganjuk" width="70" height="70">
            </td>
            <td style="vertical-align: top; font-size: 12px; line-height: 1.4;">
                <div style="font-weight: bold; font-size: 14px; color: #3565A5;">DINAS TENAGA KERJA DAN TRANSMIGRASI</div>
                <div style="font-weight: bold; font-size: 14px; color: #3565A5; margin: 2px 0;">PROVINSI JAWA TIMUR</div>
                <div style="font-weight: bold; font-size: 13px; margin: 8px 0 4px 0;">UNIT PELAKSANA TEKNIS BALAI LATIHAN KERJA (UPT BLK) NGANJUK</div>
                <div style="font-size: 11px; color: #333;">Jalan Veteran No. 87, Nganjuk, Jawa Timur</div>
                <div style="font-size: 11px; color: #333;">Telp. (0358) XXXXXXX | Email: blk.nganjuk@jatimprov.go.id</div>
            </td>
        </tr>
    </table>';
}

function safe_date($date_str, $format = 'd/m/Y') {
    if (empty($date_str)) return '—';
    $ts = strtotime($date_str);
    return ($ts !== false) ? date($format, $ts) : '—';
}

function force_pdf_headers() {
    if (!headers_sent()) header_remove('Content-Type');
}

function safe_close_conn($conn) {
    if ($conn instanceof mysqli) @$conn->close();
}

/** ====== END HELPERS ====== **/


/* ==========================================================
   MODE: GET JURUSAN — BARU! UNTUK FILTER DROPDOWN
   ========================================================== */
if (isset($_GET['mode']) && $_GET['mode'] === 'get_jurusan') {
    session_start();
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        json_exit(['error' => 'Akses ditolak'], 403);
    }

    $conn = get_db_connection();
    if (!$conn) {
        json_exit(['error' => 'Koneksi database gagal'], 500);
    }

    $sql = "SELECT id_jurusan, nama_jurusan FROM jurusan ORDER BY nama_jurusan";
    $res = $conn->query($sql);
    $jurusan = [];
    while ($row = $res->fetch_assoc()) {
        $jurusan[] = [
            'id_jurusan' => (int)$row['id_jurusan'],
            'nama_jurusan' => $row['nama_jurusan']
        ];
    }
    safe_close_conn($conn);

    json_exit([
        'success' => true,
        'data' => $jurusan
    ]);
}


/* ==========================================================
   MODE: PDF PER PESERTA
   ========================================================== */
if (isset($_GET['download']) && $_GET['download'] === 'pdf') {
    session_start();
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        http_response_code(403);
        exit('Akses ditolak');
    }

    $conn = get_db_connection();
    if (!$conn instanceof mysqli) exit('Koneksi database gagal.');
    if (!load_dompdf_autoload()) exit('Error: DOMPDF tidak ditemukan.');

    $OptionsClass = '\Dompdf\Options';
    $DompdfClass = '\Dompdf\Dompdf';

    $jurusan = $_GET['jurusan'] ?? null;
    $where = "1=1";
    $params = [];
    $types = "";

    if ($jurusan && $jurusan !== 'all') {
        $where .= " AND j.nama_jurusan = ?";
        $params[] = $jurusan;
        $types .= "s";
    }

    $sql = "
        SELECT p.id_peserta, p.nama_peserta, p.NIK, p.tgl_daftar, p.status,
               j.nama_jurusan, l.total_poin, l.tanggal_tes
        FROM peserta p
        LEFT JOIN jurusan j ON p.id_jurusan=j.id_jurusan
        LEFT JOIN laporan l ON p.id_peserta=l.id_peserta
        WHERE $where
        ORDER BY p.tgl_daftar DESC
    ";

    $stmt = $conn->prepare($sql);
    if (!empty($params)) $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $peserta = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    safe_close_conn($conn);

    $html = '<!doctype html>
    <html>
    <head>
        <meta charset="utf-8">
        <style>
            body { font-family: Arial, sans-serif; margin: 25px; color: #000; }
            .page { page-break-after: always; }
            .header { margin-bottom: 20px; }
            .header h1 { color: #3565A5; margin: 10px 0; font-size: 20px; text-align: center; }
            .content { margin-top: 20px; }
            .section { margin-bottom: 30px; }
            .section h2 { color: #3565A5; border-bottom: 2px solid #3565A5; padding-bottom: 5px; }
            table { width: 100%; border-collapse: collapse; margin-top: 10px; }
            th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
            th { background-color: #f0f9ff; font-weight: bold; }
            .highlight { background: #eef7ff; padding: 10px; margin-top: 10px; border-left: 3px solid #3498db; }
        </style>
    </head>
    <body>';

    foreach ($peserta as $p) {
        $html .= '<div class="page">' . get_kop_surat_html() . '
            <div class="header">
                <h1>Data Peserta</h1>
            </div>

            <div class="content">
                <div class="section">
                    <table>
                        <tr>
                            <td><strong>Nama:</strong></td>
                            <td>' . htmlspecialchars($p['nama_peserta']) . '</td>
                        </tr>
                        <tr>
                            <td><strong>NIK:</strong></td>
                            <td>' . htmlspecialchars($p['NIK']) . '</td>
                        </tr>
                        <tr>
                            <td><strong>Jurusan:</strong></td>
                            <td>' . htmlspecialchars($p['nama_jurusan']) . '</td>
                        </tr>
                        <tr>
                            <td><strong>Status:</strong></td>
                            <td>' . htmlspecialchars($p['status']) . '</td>
                        </tr>
                        <tr>
                            <td><strong>Tanggal Daftar:</strong></td>
                            <td>' . safe_date($p['tgl_daftar'], 'd F Y') . '</td>
                        </tr>
                    </table>';

        if (($p['status'] ?? '') === 'seleksi') {
            $html .= '<div class="highlight">
                <p><strong>Total Poin:</strong> ' . (int)($p['total_poin'] ?? 0) . '</p>
                <p><strong>Tanggal Tes:</strong> ' . safe_date($p['tanggal_tes']) . '</p>
            </div>';
        }

        $html .= '</div></div></div>';
    }

    $html .= '</body></html>';

    force_pdf_headers();
    @ob_end_clean();

    $options = new $OptionsClass();
    $options->set('defaultFont','Arial');
    $dompdf = new $DompdfClass($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4','portrait');
    $dompdf->render();
    $dompdf->stream('peserta.pdf', ['Attachment'=>false]);
    exit;
}


/* ==========================================================
   MODE: PDF TABLE
   ========================================================== */
if (isset($_GET['download']) && $_GET['download'] === 'pdf_table') {
    session_start();
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        http_response_code(403);
        exit('Akses ditolak');
    }

    $conn = get_db_connection();
    $sql = "
        SELECT p.nama_peserta, p.NIK, j.nama_jurusan, p.status, l.total_poin, l.tanggal_tes
        FROM peserta p
        LEFT JOIN jurusan j ON p.id_jurusan=j.id_jurusan
        LEFT JOIN laporan l ON p.id_peserta=l.id_peserta
        ORDER BY p.tgl_daftar DESC
    ";
    $rows = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);
    safe_close_conn($conn);

    if (!load_dompdf_autoload()) exit('Error DOMPDF');

    $OptionsClass = '\Dompdf\Options';
    $DompdfClass = '\Dompdf\Dompdf';

    $logo_file = __DIR__ . '/images/profile.png';
    $logo_base64 = 'image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=';
    if (file_exists($logo_file)) {
        $logo_data = @file_get_contents($logo_file);
        if ($logo_data !== false) {
            $logo_base64 = 'image/png;base64,' . base64_encode($logo_data);
        }
    }

    $html = '<!doctype html>
    <html>
    <head>
        <meta charset="utf-8">
        <style>
            body { font-family: Arial, sans-serif; margin: 25px; color: #000; }
            table { width: 100%; border-collapse: collapse; margin-top: 10px; }
            th, td { border: 1px solid #999; padding: 8px; text-align: left; }
            th { background-color: #f0f9ff; font-weight: bold; }
            img { width: 40px; height: 40px; border-radius: 4px; object-fit: contain; }
            h2 { margin: 15px 0 20px; color: #3565A5; text-align: center; }
        </style>
    </head>
    <body>' . get_kop_surat_html() . '
        <h2>Daftar Peserta Pelatihan</h2>
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Logo</th>
                    <th>Nama</th>
                    <th>NIK</th>
                    <th>Jurusan</th>
                    <th>Status</th>
                    <th>Poin</th>
                </tr>
            </thead>
            <tbody>';

    $i = 1;
    foreach ($rows as $r) {
        $html .= '<tr>
            <td>' . $i++ . '</td>
            <td><img src="' . $logo_base64 . '" alt="Logo UPT BLK Nganjuk" width="40" height="40"></td>
            <td>' . htmlspecialchars($r['nama_peserta']) . '</td>
            <td>' . htmlspecialchars($r['NIK']) . '</td>
            <td>' . htmlspecialchars($r['nama_jurusan']) . '</td>
            <td>' . htmlspecialchars($r['status']) . '</td>
            <td>' . (isset($r['total_poin']) ? (int)$r['total_poin'] : '—') . '</td>
        </tr>';
    }

    $html .= '</tbody></table></body></html>';

    force_pdf_headers();
    @ob_end_clean();

    $options = new $OptionsClass();
    $dompdf = new $DompdfClass($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4','portrait');
    $dompdf->render();
    $dompdf->stream('peserta_ringkas.pdf', ['Attachment'=>false]);
    exit;
}


/* ==========================================================
   MODE: CSV EXPORT
   ========================================================== */
if (isset($_GET['download']) && $_GET['download'] === 'csv') {
    session_start();
    if ($_SESSION['role'] !== 'admin') {
        http_response_code(403);
        exit('Akses ditolak');
    }

    $conn = get_db_connection();
    $sql = "
        SELECT p.*, j.nama_jurusan, l.total_poin, l.tanggal_tes
        FROM peserta p
        LEFT JOIN jurusan j ON p.id_jurusan=j.id_jurusan
        LEFT JOIN laporan l ON p.id_peserta=l.id_peserta
        ORDER BY p.tgl_daftar DESC
    ";

    $res = $conn->query($sql);

    @ob_end_clean();
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=peserta.csv');

    $out = fopen('php://output','w');
    fputcsv($out, ['ID','Nama','NIK','Email','HP','Jurusan','Status','Tanggal Daftar','Total Poin','Tanggal Tes']);

    while ($row = $res->fetch_assoc()) {
        fputcsv($out, [
            $row['id_peserta'], $row['nama_peserta'], $row['NIK'], $row['email'],
            $row['NO_HP'], $row['nama_jurusan'], $row['status'],
            $row['tgl_daftar'], $row['total_poin'], $row['tanggal_tes']
        ]);
    }

    fclose($out);
    safe_close_conn($conn);
    exit;
}


/* ==========================================================
   MODE: RANKING PDF
   ========================================================== */
if (isset($_GET['mode']) && $_GET['mode'] === 'pdf') {
    session_start();
    if ($_SESSION['role'] !== 'admin') {
        http_response_code(403);
        exit('Akses ditolak');
    }

    $conn = get_db_connection();
    if (!load_dompdf_autoload()) exit('Dompdf tidak ditemukan');

    $OptionsClass = '\Dompdf\Options';
    $DompdfClass = '\Dompdf\Dompdf';

    $limit = (int)($_GET['limit'] ?? 20);
    $jurusan = $_GET['jurusan'] ?? null;

    $where = "p.status='seleksi' AND l.total_poin IS NOT NULL";
    $params = []; $types = "";

    if ($jurusan && $jurusan !== 'semua') {
        $where .= " AND j.nama_jurusan = ?";
        $params[] = str_replace('_',' ',$jurusan);
        $types .= "s";
    }

    $sql = "
        SELECT p.nama_peserta, p.NIK, j.nama_jurusan, l.total_poin, l.tanggal_tes
        FROM peserta p
        INNER JOIN jurusan j ON p.id_jurusan=j.id_jurusan
        INNER JOIN laporan l ON p.id_peserta=l.id_peserta
        WHERE $where
        ORDER BY l.total_poin DESC, l.tanggal_tes ASC
        LIMIT ?
    ";
    $params[] = $limit; $types .= "i";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $hasil = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    safe_close_conn($conn);

    $html = '<!doctype html>
    <html>
    <head>
        <meta charset="utf-8">
        <style>
            body { font-family: Arial, sans-serif; margin: 25px; color: #000; }
            table { width: 100%; border-collapse: collapse; margin-top: 10px; }
            th, td { border: 1px solid #999; padding: 8px; text-align: left; }
            th { background-color: #f0f9ff; font-weight: bold; }
            h2 { margin: 15px 0 10px; color: #3565A5; text-align: center; font-size: 22px; }
            .subtitle { text-align: center; margin: 0 0 20px; font-size: 16px; }
            .kop-surat { margin-bottom: 20px; }
        </style>
    </head>
    <body>
        <div class="kop-surat">' . get_kop_surat_html() . '</div>
        <h2>Ranking Peserta Seleksi</h2>
        <div class="subtitle">
            ' . ($jurusan && $jurusan !== 'semua' 
                ? 'Jurusan: <strong>' . htmlspecialchars(str_replace('_',' ',$jurusan)) . '</strong>' 
                : 'Semua Jurusan') . '
        </div>
        <table>
            <thead>
                <tr>
                    <th>Rank</th>
                    <th>Nama</th>
                    <th>NIK</th>
                    <th>Jurusan</th>
                    <th>Poin</th>
                    <th>Tanggal Tes</th>
                </tr>
            </thead>
            <tbody>';

    $rank = 1;
    foreach ($hasil as $row) {
        $html .= '<tr>
            <td>' . $rank++ . '</td>
            <td>' . htmlspecialchars($row['nama_peserta']) . '</td>
            <td>' . htmlspecialchars($row['NIK']) . '</td>
            <td>' . htmlspecialchars($row['nama_jurusan']) . '</td>
            <td>' . (int)$row['total_poin'] . '</td>
            <td>' . safe_date($row['tanggal_tes'], 'd/m/Y H:i') . '</td>
        </tr>';
    }

    $html .= '</tbody></table></body></html>';

    force_pdf_headers();
    @ob_end_clean();

    $options = new $OptionsClass();
    $dompdf = new $DompdfClass($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4','portrait');
    $dompdf->render();
    $dompdf->stream("ranking.pdf", ['Attachment'=>false]);
    exit;
}


/* ==========================================================
   MODE: RANKING EXCEL (XLSX)
   ========================================================== */
if (isset($_GET['download']) && $_GET['download'] === 'excel_ranking') {
    session_start();
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        http_response_code(403);
        exit('Akses ditolak');
    }

    $autoload_found = false;
    $candidates = [
        __DIR__ . '/vendor/autoload.php',
        __DIR__ . '/../vendor/autoload.php',
        __DIR__ . '/../../vendor/autoload.php',
    ];
    foreach ($candidates as $file) {
        if (file_exists($file)) {
            require_once $file;
            $autoload_found = true;
            break;
        }
    }
    if (!$autoload_found) {
        exit('Error: PhpSpreadsheet tidak ditemukan. Jalankan: composer require phpoffice/phpspreadsheet');
    }

    $conn = get_db_connection();

    $limit = (int)($_GET['limit'] ?? 20);
    $jurusan = $_GET['jurusan'] ?? null;

    $where = "p.status='seleksi' AND l.total_poin IS NOT NULL";
    $params = []; $types = "";

    if ($jurusan && $jurusan !== 'semua') {
        $where .= " AND j.nama_jurusan = ?";
        $params[] = str_replace('_',' ',$jurusan);
        $types .= "s";
    }

    $sql = "
        SELECT p.nama_peserta, p.NIK, j.nama_jurusan, l.total_poin, l.tanggal_tes
        FROM peserta p
        INNER JOIN jurusan j ON p.id_jurusan=j.id_jurusan
        INNER JOIN laporan l ON p.id_peserta=l.id_peserta
        WHERE $where
        ORDER BY l.total_poin DESC, l.tanggal_tes ASC
        LIMIT ?
    ";
    $params[] = $limit; $types .= "i";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $hasil = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    safe_close_conn($conn);

    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    $sheet->setCellValue('A1', 'Ranking Peserta Seleksi');
    $sheet->mergeCells('A1:F1');
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
    $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

    $subheader = ($jurusan && $jurusan !== 'semua') 
        ? 'Jurusan: ' . str_replace('_',' ',$jurusan) 
        : 'Semua Jurusan';
    $sheet->setCellValue('A2', $subheader);
    $sheet->mergeCells('A2:F2');
    $sheet->getStyle('A2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

    $sheet->setCellValue('A4', 'Rank');
    $sheet->setCellValue('B4', 'Nama');
    $sheet->setCellValue('C4', 'NIK');
    $sheet->setCellValue('D4', 'Jurusan');
    $sheet->setCellValue('E4', 'Poin');
    $sheet->setCellValue('F4', 'Tanggal Tes');

    $headerRange = 'A4:F4';
    $sheet->getStyle($headerRange)->getFont()->setBold(true);
    $sheet->getStyle($headerRange)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle($headerRange)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

    $row = 5;
    $rank = 1;
    foreach ($hasil as $r) {
        $sheet->setCellValue('A' . $row, $rank++);
        $sheet->setCellValue('B' . $row, $r['nama_peserta']);
        $sheet->setCellValue('C' . $row, $r['NIK']);
        $sheet->setCellValue('D' . $row, $r['nama_jurusan']);
        $sheet->setCellValue('E' . $row, (int)$r['total_poin']);
        $sheet->setCellValue('F' . $row, $r['tanggal_tes'] ?? '');
        $row++;
    }

    foreach (range('A','F') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    @ob_end_clean();
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="ranking_peserta.xlsx"');
    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}


/* ==========================================================
   MODE: KOREKSI JAWABAN — DAFTAR & DETAIL (DIPERBAIKI: TAMBAH FILTER JURUSAN)
   ========================================================== */
if (isset($_GET['mode']) && $_GET['mode'] === 'koreksi') {
    session_start();
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        json_exit(['error' => 'Akses ditolak'], 403);
    }

    $conn = get_db_connection();
    if (!$conn) {
        json_exit(['error' => 'Koneksi database gagal'], 500);
    }

    // Detail per peserta
    if (isset($_GET['id_peserta'])) {
        $id_peserta = (int)$_GET['id_peserta'];
        $sql = "
            SELECT 
                s.no_soal,
                s.soal,
                s.poin,    
                s.opsi_a,
                s.opsi_b,
                s.opsi_c,
                s.opsi_d,
                s.kunci_jawaban,
                dl.jawaban,
                dl.status AS status_jawaban
            FROM detail_laporan dl
            JOIN seleksi s ON dl.id_seleksi = s.id_seleksi
            JOIN laporan l ON dl.id_laporan = l.id_laporan
            WHERE l.id_peserta = ?
            ORDER BY s.no_soal ASC
        ";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id_peserta);
        $stmt->execute();
        $detail = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        $sql2 = "
            SELECT p.id_peserta, p.nama_peserta, j.nama_jurusan, l.total_poin, l.total_benar, l.total_salah, l.tanggal_tes, j.id_jurusan
            FROM peserta p
            JOIN jurusan j ON p.id_jurusan = j.id_jurusan
            JOIN laporan l ON p.id_peserta = l.id_peserta
            WHERE p.id_peserta = ?
        ";
        $stmt2 = $conn->prepare($sql2);
        $stmt2->bind_param("i", $id_peserta);
        $stmt2->execute();
        $info = $stmt2->get_result()->fetch_assoc();
        $stmt2->close();

        safe_close_conn($conn);

        json_exit([
            'success' => true,
            'type' => 'detail',
            'peserta' => $info,
            'jawaban' => array_map(function($row) {
                $benar_salah = ($row['status_jawaban'] === 'benar') ? true : false;
                return [
                    'no_soal' => (int)$row['no_soal'],
                    'soal' => $row['soal'],
                    'poin' => (int)$row['poin'],
                    'opsi' => [
                        'A' => $row['opsi_a'],
                        'B' => $row['opsi_b'],
                        'C' => $row['opsi_c'],
                        'D' => $row['opsi_d'],
                    ],
                    'kunci_jawaban' => $row['kunci_jawaban'],
                    'jawaban' => $row['jawaban'],
                    'benar_salah' => $benar_salah,
                ];
            }, $detail)
        ]);

    } else {
        // Daftar peserta untuk koreksi — DENGAN FILTER JURUSAN OPSIONAL
        $jurusan_id = $_GET['jurusan'] ?? null;

        $where = "p.status = 'seleksi'";
        $params = [];
        $types = "";

        if ($jurusan_id && is_numeric($jurusan_id)) {
            $where .= " AND j.id_jurusan = ?";
            $params[] = (int)$jurusan_id;
            $types .= "i";
        }

        $sql = "
            SELECT 
                p.id_peserta, p.nama_peserta, p.NIK,
                j.nama_jurusan, j.id_jurusan,
                l.total_poin,
                l.total_benar, l.total_salah, l.tanggal_tes
            FROM peserta p
            JOIN jurusan j ON p.id_jurusan = j.id_jurusan
            JOIN laporan l ON p.id_peserta = l.id_peserta
            WHERE $where
            ORDER BY l.tanggal_tes DESC
        ";

        if ($params) {
            $stmt = $conn->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $res = $stmt->get_result();
            $stmt->close();
        } else {
            $res = $conn->query($sql);
        }

        $peserta_koreksi = [];
        while ($row = $res->fetch_assoc()) {
            $peserta_koreksi[] = [
                'id_peserta' => (int)$row['id_peserta'],
                'nama_peserta' => $row['nama_peserta'],
                'NIK' => $row['NIK'],
                'id_jurusan' => (int)$row['id_jurusan'],
                'nama_jurusan' => $row['nama_jurusan'],
                'total_poin' => (int)($row['total_poin'] ?? 0),
                'total_benar' => (int)($row['total_benar'] ?? 0),
                'total_salah' => (int)($row['total_salah'] ?? 0),
                'tanggal_tes' => $row['tanggal_tes'] ? date('d/m/Y H:i', strtotime($row['tanggal_tes'])) : '—'
            ];
        }
        safe_close_conn($conn);

        json_exit([
            'success' => true,
            'type' => 'list',
            'peserta_koreksi' => $peserta_koreksi,
            'count' => count($peserta_koreksi)
        ]);
    }
}


/* ==========================================================
   DEFAULT: JSON API FRONTEND (HANYA 1x)
   ========================================================== */

session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    json_exit(['error' => 'Akses ditolak'], 403);
}

$conn = get_db_connection();

$stmt = $conn->prepare("SELECT username, nama_admin, foto_profil FROM admin WHERE id_admin=?");
$stmt->bind_param("i", $_SESSION['id_admin']);
$stmt->execute();
$admin = $stmt->get_result()->fetch_assoc();
$stmt->close();

$fotoAdmin = !empty($admin['foto_profil']) ? 'images/'.$admin['foto_profil'] : 'images/profile.png';

$profil = [
    'logged_in_username' => $admin['username'],
    'logged_in_admin_name' => $admin['nama_admin'],
    'logged_in_foto_profil_path' => $fotoAdmin
];

$sql = "
    SELECT p.*, j.nama_jurusan, l.total_poin, l.tanggal_tes, a.nama_admin AS added_by_name
    FROM peserta p
    LEFT JOIN jurusan j ON p.id_jurusan=j.id_jurusan
    LEFT JOIN laporan l ON p.id_peserta=l.id_peserta
    LEFT JOIN admin a ON p.id_admin=a.id_admin
    ORDER BY CASE WHEN p.status='seleksi' THEN 0 ELSE 1 END, l.total_poin DESC, l.tanggal_tes ASC
";
$res = $conn->query($sql);
$peserta_raw = $res->fetch_all(MYSQLI_ASSOC);

$peserta = [];
foreach ($peserta_raw as $p) {
    $foto = !empty($p['foto_profil']) ? 'images/'.$p['foto_profil'] : 'images/profile.png';
    $p['foto_profil_path'] = $foto;
    $p['added_by'] = ['nama_admin' => $p['added_by_name'] ?? null];
    unset($p['added_by_name']);
    $peserta[] = $p;
}

$res2 = $conn->query("SELECT nama_jurusan FROM jurusan ORDER BY nama_jurusan");
$daftar_jurusan = array_column($res2->fetch_all(MYSQLI_ASSOC), 'nama_jurusan');

$statistik = [];
foreach ($daftar_jurusan as $jur) {
    $statistik[$jur] = ['seleksi'=>0,'aktif'=>0,'drop_out'=>0,'lulus'=>0];
}
foreach ($peserta as $p) {
    $jur = $p['nama_jurusan'] ?? 'Tidak Ada';
    if (!isset($statistik[$jur])) {
        $statistik[$jur] = ['seleksi'=>0,'aktif'=>0,'drop_out'=>0,'lulus'=>0];
    }
    if (isset($statistik[$jur][$p['status']])) {
        $statistik[$jur][$p['status']]++;
    }
}

$limit = (int)($_GET['limit'] ?? 20);
$seleksi_with_poin = array_filter($peserta, function($p) {
    return ($p['status'] === 'seleksi') && ($p['total_poin'] !== null);
});

usort($seleksi_with_poin, function($a, $b) {
    if ($b['total_poin'] != $a['total_poin']) {
        return $b['total_poin'] <=> $a['total_poin'];
    }
    $dateA = strtotime($a['tanggal_tes'] ?? '9999-12-31');
    $dateB = strtotime($b['tanggal_tes'] ?? '9999-12-31');
    return $dateA <=> $dateB;
});

$rankingGlobal = [];
$rank = 1;
foreach (array_slice($seleksi_with_poin, 0, $limit) as $p) {
    $rankingGlobal[$p['id_peserta']] = $rank++;
}

$rankingPerJurusan = [];
$grouped = [];
foreach ($seleksi_with_poin as $p) {
    $jur = $p['nama_jurusan'] ?? 'Tidak Ada';
    if (!isset($grouped[$jur])) {
        $grouped[$jur] = [];
    }
    $grouped[$jur][] = $p;
}

foreach ($grouped as $jur => $list) {
    usort($list, function($a, $b) {
        if ($b['total_poin'] != $a['total_poin']) {
            return $b['total_poin'] <=> $a['total_poin'];
        }
        $dateA = strtotime($a['tanggal_tes'] ?? '9999-12-31');
        $dateB = strtotime($b['tanggal_tes'] ?? '9999-12-31');
        return $dateA <=> $dateB;
    });
    $rank = 1;
    foreach (array_slice($list, 0, $limit) as $p) {
        $rankingPerJurusan[$jur][$p['id_peserta']] = $rank++;
    }
}

json_exit([
    'profil_admin' => $profil,
    'peserta' => $peserta,
    'jurusan' => $daftar_jurusan,
    'statistik' => $statistik,
    'ranking_global' => $rankingGlobal,
    'ranking_per_jurusan' => $rankingPerJurusan
]);

?>