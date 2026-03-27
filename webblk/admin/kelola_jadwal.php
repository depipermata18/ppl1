<?php
// ======================================================================
// API JADWAL — FINAL VERSION • ADMIN BEBAS EDIT SEMUA JADWAL & JURUSAN
// ======================================================================

header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

session_start();
ob_start();

// ======================================================================
// 1. OPTIONS PREFLIGHT
// ======================================================================
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// ======================================================================
// 2. VALIDASI LOGIN ADMIN
// ======================================================================
if (
    empty($_SESSION['role']) ||
    $_SESSION['role'] !== 'admin' ||
    empty($_SESSION['id_admin'])
) {
    http_response_code(403);
    echo json_encode(['error' => 'Anda harus login sebagai admin.']);
    exit;
}

$id_admin = (int) $_SESSION['id_admin'];
if ($id_admin <= 0) {
    http_response_code(403);
    echo json_encode(['error' => 'Session admin tidak valid.']);
    exit;
}

// ======================================================================
// 3. KONEKSI DATABASE
// ======================================================================
$db_path = __DIR__ . '/../config/database.php';

if (!file_exists($db_path)) {
    http_response_code(500);
    echo json_encode(['error' => 'File database.php tidak ditemukan.']);
    exit;
}

$conn = include $db_path;

if (!$conn || !($conn instanceof mysqli)) {
    http_response_code(500);
    echo json_encode(['error' => 'Gagal tersambung ke database.']);
    exit;
}

// ======================================================================
// 3b. AMBIL id_jurusan ADMIN
// ======================================================================
$id_jurusan = 0;
$stmt_jur = $conn->prepare("SELECT id_jurusan FROM admin WHERE id_admin = ?");
$stmt_jur->bind_param("i", $id_admin);
$stmt_jur->execute();
$result_jur = $stmt_jur->get_result();
if ($row_jur = $result_jur->fetch_assoc()) {
    $id_jurusan = (int) $row_jur['id_jurusan'];
}
$stmt_jur->close();

$_SESSION['id_jurusan'] = $id_jurusan;

// ======================================================================
// 4. AMBIL PROFIL ADMIN
// ======================================================================
$logged_in_admin_name  = "Admin";
$logged_in_username    = "admin";
$logged_in_foto_profil = "images/profile.png";

$stmt = $conn->prepare("SELECT nama_admin, username, foto_profil FROM admin WHERE id_admin = ?");
$stmt->bind_param("i", $id_admin);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $logged_in_admin_name  = $row['nama_admin'] ?: $logged_in_admin_name;
    $logged_in_username    = $row['username'] ?: $logged_in_username;
    $logged_in_foto_profil = "images/" . ($row['foto_profil'] ?: "profile.png");
}
$stmt->close();

// ======================================================================
// 5. ENDPOINT INFO ADMIN
// ======================================================================
if (isset($_GET['info'])) {
    echo json_encode([
        'logged_in_admin_name'  => $logged_in_admin_name,
        'logged_in_username'    => $logged_in_username,
        'logged_in_foto_profil' => $logged_in_foto_profil,
        'id_admin'              => $id_admin,
        'id_jurusan'            => $id_jurusan
    ]);
    exit;
}

$input = json_decode(file_get_contents("php://input"), true) ?: [];
$method = $_SERVER['REQUEST_METHOD'];

// ======================================================================
// 6. GET — Ambil Semua Jadwal
// ======================================================================
if ($method === 'GET') {
    $sql = "
        SELECT j.*, ju.nama_jurusan, ad.nama_admin
        FROM jadwal j
        LEFT JOIN jurusan ju ON j.id_jurusan = ju.id_jurusan
        LEFT JOIN admin ad ON j.id_admin = ad.id_admin
        ORDER BY j.waktu_mulai ASC
    ";

    $jadwal = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);
    $jurusan = $conn->query("SELECT id_jurusan, nama_jurusan FROM jurusan ORDER BY nama_jurusan")->fetch_all(MYSQLI_ASSOC);

    echo json_encode([
        'logged_in_admin_name'  => $logged_in_admin_name,
        'logged_in_username'    => $logged_in_username,
        'logged_in_foto_profil' => $logged_in_foto_profil,
        'jadwal'                => $jadwal,
        'jurusan'               => $jurusan
    ]);
    exit;
}

// ======================================================================
// 7. POST — Tambah Jadwal (WAKTU WAJIB DIISI)
// ======================================================================
if ($method === 'POST') {
    $nama  = trim($input['nama_jadwal'] ?? '');
    $lok   = trim($input['lokasi'] ?? '');
    $ket   = trim($input['keterangan'] ?? '');
    $jur   = (int)($input['id_jurusan'] ?? 0);
    $mulai = trim($input['waktu_mulai'] ?? '');
    $seles = trim($input['waktu_selesai'] ?? '');

    // 🔴 VALIDASI: SEMUA FIELD WAJIB, TERMASUK WAKTU
    if (!$nama || !$lok || !$jur || !$mulai || !$seles) {
        http_response_code(400);
        echo json_encode(['error' => 'Nama jadwal, lokasi, jurusan, waktu mulai, dan waktu selesai wajib diisi.']);
        exit;
    }

    // Parse waktu
    $dt_mulai = DateTime::createFromFormat('Y-m-d\TH:i', $mulai);
    $dt_seles = DateTime::createFromFormat('Y-m-d\TH:i', $seles);

    if (!$dt_mulai || !$dt_seles) {
        http_response_code(400);
        echo json_encode(['error' => 'Format waktu tidak valid.']);
        exit;
    }

    if ($dt_mulai >= $dt_seles) {
        http_response_code(400);
        echo json_encode(['error' => 'Waktu mulai harus lebih awal dari waktu selesai.']);
        exit;
    }

    $mulai_sql = $dt_mulai->format('Y-m-d H:i:s');
    $seles_sql = $dt_seles->format('Y-m-d H:i:s');

    $stmt = $conn->prepare("
        INSERT INTO jadwal 
        (nama_jadwal, lokasi, keterangan, id_admin, id_jurusan, waktu_mulai, waktu_selesai)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");

    if (!$stmt) {
        http_response_code(500);
        echo json_encode(['error' => 'Gagal menyiapkan query.']);
        exit;
    }

    $stmt->bind_param("sssisss", $nama, $lok, $ket, $id_admin, $jur, $mulai_sql, $seles_sql);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Jadwal berhasil ditambahkan!']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Gagal menyimpan ke database.']);
    }

    $stmt->close();
    exit;
}

// ======================================================================
// 8. PUT — Update Jadwal (WAKTU WAJIB DIISI)
// ======================================================================
if ($method === 'PUT') {
    $id    = (int)($input['id_jadwal'] ?? 0);
    $nama  = trim($input['nama_jadwal'] ?? '');
    $lok   = trim($input['lokasi'] ?? '');
    $ket   = trim($input['keterangan'] ?? '');
    $jur   = (int)($input['id_jurusan'] ?? 0);
    $mulai = trim($input['waktu_mulai'] ?? '');
    $seles = trim($input['waktu_selesai'] ?? '');

    if (!$id || !$nama || !$lok || !$jur || !$mulai || !$seles) {
        http_response_code(400);
        echo json_encode(['error' => 'Semua field wajib diisi, termasuk waktu mulai dan selesai.']);
        exit;
    }

    // Cek eksistensi
    $stmt_check = $conn->prepare("SELECT id_jadwal FROM jadwal WHERE id_jadwal = ?");
    $stmt_check->bind_param("i", $id);
    $stmt_check->execute();
    if ($stmt_check->get_result()->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['error' => 'Jadwal tidak ditemukan.']);
        exit;
    }
    $stmt_check->close();

    $dt_mulai = DateTime::createFromFormat('Y-m-d\TH:i', $mulai);
    $dt_seles = DateTime::createFromFormat('Y-m-d\TH:i', $seles);

    if (!$dt_mulai || !$dt_seles) {
        http_response_code(400);
        echo json_encode(['error' => 'Format waktu tidak valid.']);
        exit;
    }

    if ($dt_mulai >= $dt_seles) {
        http_response_code(400);
        echo json_encode(['error' => 'Waktu mulai harus lebih awal dari waktu selesai.']);
        exit;
    }

    $mulai_sql = $dt_mulai->format('Y-m-d H:i:s');
    $seles_sql = $dt_seles->format('Y-m-d H:i:s');

    $stmt = $conn->prepare("
        UPDATE jadwal 
        SET nama_jadwal=?, lokasi=?, keterangan=?, id_jurusan=?, waktu_mulai=?, waktu_selesai=?
        WHERE id_jadwal=?
    ");
    $stmt->bind_param("sssissi", $nama, $lok, $ket, $jur, $mulai_sql, $seles_sql, $id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Jadwal berhasil diperbarui!']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Gagal memperbarui database.']);
    }

    $stmt->close();
    exit;
}

// ======================================================================
// 9. DELETE — Hapus Jadwal
// ======================================================================
if ($method === 'DELETE') {
    $id = (int)($input['id_jadwal'] ?? 0);
    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'ID jadwal tidak valid.']);
        exit;
    }

    $stmt = $conn->prepare("DELETE FROM jadwal WHERE id_jadwal = ?");
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(['error' => 'Gagal menyiapkan query hapus.']);
        exit;
    }
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();

    echo json_encode(['success' => true, 'message' => 'Jadwal berhasil dihapus!']);
    exit;
}

// ======================================================================
// 10. METHOD NOT ALLOWED
// ======================================================================
http_response_code(405);
echo json_encode(['error' => 'Metode tidak diperbolehkan.']);
exit;