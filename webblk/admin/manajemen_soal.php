<?php
header('Content-Type: application/json');
session_start();

// Redirect jika belum login
if (!isset($_SESSION['id_admin'])) {
    echo json_encode([
        "html" => "<div style='padding:40px;text-align:center;color:red;font-weight:bold'>
                    Anda harus login terlebih dahulu.<br>
                   </div>",
        "redirect" => "login.html"
    ]);
    exit;
}

ob_start();
include '../config/database.php';
$buffered_output = ob_get_contents();
ob_end_clean();

if ($buffered_output !== '') {
    http_response_code(500);
    error_log("Output sebelum JSON dari config/database.php: " . trim($buffered_output));
    echo json_encode(['error' => 'Output tidak valid dari config/database.php']);
    exit;
}

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Akses ditolak. Login sebagai admin.']);
    exit;
}

$id_admin = $_SESSION['id_admin']; // dari session

// === AMBIL DATA PROFIL ADMIN UNTUK HEADER ===
$logged_in_admin_name = 'Admin';
$logged_in_username = 'admin';
$logged_in_foto_profil_path = 'images/profile.png';

$stmt_profile = $conn->prepare("SELECT nama_admin, username, foto_profil FROM admin WHERE id_admin = ?");
if ($stmt_profile) {
    $stmt_profile->bind_param("s", $id_admin);
    $stmt_profile->execute();
    $profile_result = $stmt_profile->get_result();
    if ($profile_row = $profile_result->fetch_assoc()) {
        $logged_in_admin_name = $profile_row['nama_admin'] ?? 'Admin';
        $logged_in_username = $profile_row['username'] ?? 'admin';
        $foto_file = $profile_row['foto_profil'] ?? 'profile.png';
        $logged_in_foto_profil_path = 'images/' . $foto_file;
    }
    $stmt_profile->close();
}

ini_set('upload_max_filesize', '0');
ini_set('post_max_size', '0');
ini_set('memory_limit', '-1');
ini_set('max_execution_time', '0');
ini_set('max_input_time', '0');

try {
    // === HAPUS SOAL ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hapus_id'])) {

    $id_hapus   = (int) $_POST['hapus_id'];
    $id_jurusan = $_SESSION['id_jurusan'];

    if ($id_hapus <= 0) {
        echo json_encode(['error' => 'ID tidak valid']);
        exit;
    }

    $stmt = $conn->prepare(
        "DELETE FROM seleksi WHERE id_seleksi = ? AND id_jurusan = ?"
    );

    $stmt->bind_param("ii", $id_hapus, $id_jurusan);
    $stmt->execute();

    if ($stmt->affected_rows === 0) {
        echo json_encode(['error' => 'Data tidak ditemukan']);
        exit;
    }

    echo json_encode(['success' => true]);
    exit;
}


    // === UPDATE WAKTU SOAL ===
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_time') {
        $ids = json_decode($_POST['ids'], true);
        $waktu_mulai_raw = $_POST['waktu_mulai'];
        $waktu_selesai_raw = $_POST['waktu_selesai'];
        $id_jurusan = $_POST['id_jurusan'];

        if (!is_array($ids) || empty($ids) || !$waktu_mulai_raw || !$waktu_selesai_raw || !$id_jurusan) {
            echo json_encode([
                'logged_in_admin_name' => $logged_in_admin_name,
                'logged_in_username' => $logged_in_username,
                'logged_in_foto_profil_path' => $logged_in_foto_profil_path,
                'error' => 'Data tidak valid.'
            ]);
            exit;
        }

        $ts_mulai = strtotime($waktu_mulai_raw);
        $ts_selesai = strtotime($waktu_selesai_raw);
        if ($ts_mulai === false || $ts_selesai === false || $ts_mulai >= $ts_selesai) {
            echo json_encode([
                'logged_in_admin_name' => $logged_in_admin_name,
                'logged_in_username' => $logged_in_username,
                'logged_in_foto_profil_path' => $logged_in_foto_profil_path,
                'error' => 'Waktu tidak valid.'
            ]);
            exit;
        }

        $waktu_mulai = date('Y-m-d H:i:s', $ts_mulai);
        $waktu_selesai = date('Y-m-d H:i:s', $ts_selesai);

        $placeholders = str_repeat('?,', count($ids) - 1) . '?';
        $sql = "UPDATE seleksi SET waktu_mulai = ?, waktu_selesai = ? WHERE id_seleksi IN ($placeholders) AND id_jurusan = ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) throw new Exception("Error preparing update: " . $conn->error);

        $types = str_repeat('s', count($ids)) . 's';
        $params = array_merge([$waktu_mulai, $waktu_selesai], $ids, [$id_jurusan]);
        $stmt->bind_param("ss" . $types, ...$params);
        $stmt->execute();
        $stmt->close();

        echo json_encode([
            'logged_in_admin_name' => $logged_in_admin_name,
            'logged_in_username' => $logged_in_username,
            'logged_in_foto_profil_path' => $logged_in_foto_profil_path,
            'success' => 'Waktu soal berhasil diperbarui.'
        ]);
        exit; // 🔥 WAJIB ADA
    }

    // ✅ === TAMBAHAN BARU: UPDATE WAKTU BULK DARI POPUP MODAL ===
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_time_bulk') {
        $ids = json_decode($_POST['ids'], true);
        $waktu_mulai_raw = $_POST['waktu_mulai'];
        $waktu_selesai_raw = $_POST['waktu_selesai'];
        $id_jurusan = $_POST['id_jurusan'];

        if (!is_array($ids) || empty($ids) || !$waktu_mulai_raw || !$waktu_selesai_raw || !$id_jurusan) {
            echo json_encode([
                'logged_in_admin_name' => $logged_in_admin_name,
                'logged_in_username' => $logged_in_username,
                'logged_in_foto_profil_path' => $logged_in_foto_profil_path,
                'error' => 'Data tidak valid.'
            ]);
            exit;
        }

        $ts_mulai = strtotime($waktu_mulai_raw);
        $ts_selesai = strtotime($waktu_selesai_raw);
        if ($ts_mulai === false || $ts_selesai === false || $ts_mulai >= $ts_selesai) {
            echo json_encode([
                'logged_in_admin_name' => $logged_in_admin_name,
                'logged_in_username' => $logged_in_username,
                'logged_in_foto_profil_path' => $logged_in_foto_profil_path,
                'error' => 'Waktu tidak valid.'
            ]);
            exit;
        }

        $waktu_mulai = date('Y-m-d H:i:s', $ts_mulai);
        $waktu_selesai = date('Y-m-d H:i:s', $ts_selesai);

        $placeholders = str_repeat('?,', count($ids) - 1) . '?';
        $sql = "UPDATE seleksi SET waktu_mulai = ?, waktu_selesai = ? WHERE id_seleksi IN ($placeholders) AND id_jurusan = ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            echo json_encode([
                'logged_in_admin_name' => $logged_in_admin_name,
                'logged_in_username' => $logged_in_username,
                'logged_in_foto_profil_path' => $logged_in_foto_profil_path,
                'error' => 'Gagal menyiapkan query update.'
            ]);
            exit;
        }

        // Pastikan semua ID adalah integer
        $int_ids = array_map('intval', $ids);
        $types = str_repeat('i', count($int_ids)) . 'i';
        $params = array_merge([$waktu_mulai, $waktu_selesai], $int_ids, [(int)$id_jurusan]);
        $stmt->bind_param("ss" . $types, ...$params);
        $stmt->execute();
        $affected_rows = $stmt->affected_rows;
        $stmt->close();

        echo json_encode([
            'logged_in_admin_name' => $logged_in_admin_name,
            'logged_in_username' => $logged_in_username,
            'logged_in_foto_profil_path' => $logged_in_foto_profil_path,
            'success' => "Waktu berhasil diperbarui untuk $affected_rows soal."
        ]);
        exit;
    }

    // === TAMBAH SOAL ===
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['hapus_id']) && !isset($_POST['action'])) {
        $id_jurusan = $_POST['id_jurusan'] ?? '';

        if (!$id_jurusan) {
            echo json_encode([
                'logged_in_admin_name' => $logged_in_admin_name,
                'logged_in_username' => $logged_in_username,
                'logged_in_foto_profil_path' => $logged_in_foto_profil_path,
                'error' => 'Jurusan tidak valid'
            ]);
            exit;
        }

        $soal = trim($_POST['soal'] ?? '');
        $opsiA = trim($_POST['opsi_a'] ?? '');
        $opsiB = trim($_POST['opsi_b'] ?? '');
        $opsiC = trim($_POST['opsi_c'] ?? '');
        $opsiD = trim($_POST['opsi_d'] ?? '');
        $kunci = $_POST['kunci_jawaban'] ?? '';
        $poin = (int)($_POST['poin'] ?? 10);
        $waktu_mulai_raw = $_POST['waktu_mulai'] ?? '';
        $waktu_selesai_raw = $_POST['waktu_selesai'] ?? '';

        // 🔥 HANYA OPSI A-D
        if (!$soal || !$opsiA || !$opsiB || !$opsiC || !$opsiD) {
            $error = "Semua field soal dan opsi (A-D) wajib diisi.";
        } elseif (!in_array($kunci, ['A','B','C','D'])) { // 🔥 HANYA A-D
            $error = "Kunci jawaban harus A, B, C, atau D.";
        } elseif ($poin <= 0) {
            $error = "Poin harus lebih dari 0.";
        } elseif (!$waktu_mulai_raw || !$waktu_selesai_raw) {
            $error = "Waktu mulai dan selesai wajib diisi.";
        } else {
            $ts_mulai = strtotime($waktu_mulai_raw);
            $ts_selesai = strtotime($waktu_selesai_raw);
            if ($ts_mulai === false || $ts_selesai === false || $ts_mulai >= $ts_selesai) {
                $error = "Waktu tidak valid.";
            } else {
                $waktu_mulai = date('Y-m-d H:i:s', $ts_mulai);
                $waktu_selesai = date('Y-m-d H:i:s', $ts_selesai);

                $gambar_soal_path = null;
                if (!empty($_FILES['gambar_soal']['name'])) {
                    $upload_dir_server = __DIR__ . '/soal/';
                    $upload_dir_url = 'soal/';
                    if (!is_dir($upload_dir_server)) mkdir($upload_dir_server, 0777, true);

                    if ($_FILES['gambar_soal']['error'] === UPLOAD_ERR_OK) {
                        $allowed_ext = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                        $ext = strtolower(pathinfo($_FILES['gambar_soal']['name'], PATHINFO_EXTENSION));
                        if (in_array($ext, $allowed_ext)) {
                            $filename = 'soal_' . uniqid() . '.' . $ext;
                            $target_server = $upload_dir_server . $filename;
                            if (move_uploaded_file($_FILES['gambar_soal']['tmp_name'], $target_server)) {
                                $gambar_soal_path = $upload_dir_url . $filename;
                            } else {
                                $error = "Gagal menyimpan gambar.";
                            }
                        } else {
                            $error = "Format gambar tidak didukung.";
                        }
                    } else {
                        $error = "Gagal upload gambar.";
                    }
                }

                if (!isset($error)) {
                    $stmt_no = $conn->prepare("SELECT IFNULL(MAX(no_soal), 0) + 1 AS next_no FROM seleksi WHERE id_jurusan = ?");
                    $stmt_no->bind_param("s", $id_jurusan);
                    $stmt_no->execute();
                    $row_no = $stmt_no->get_result()->fetch_assoc();
                    $no_soal = (int)$row_no['next_no'];
                    $stmt_no->close();

                    // 🔥 SIMPAN id_admin (dari session) + id_jurusan (dari form)
                    $stmt_insert = $conn->prepare("
                        INSERT INTO seleksi 
                        (id_admin, id_jurusan, no_soal, soal, opsi_a, opsi_b, opsi_c, opsi_d, 
                         kunci_jawaban, poin, waktu_mulai, waktu_selesai, gambar_soal) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    if ($stmt_insert) {
                        $stmt_insert->bind_param(
                            "ssissssssisss",
                            $id_admin,       // ← dari session
                            $id_jurusan,    // ← dari form
                            $no_soal,
                            $soal,
                            $opsiA,
                            $opsiB,
                            $opsiC,
                            $opsiD,
                            $kunci,
                            $poin,
                            $waktu_mulai,
                            $waktu_selesai,
                            $gambar_soal_path
                        );
                        if ($stmt_insert->execute()) {
                            echo json_encode([
                                'logged_in_admin_name' => $logged_in_admin_name,
                                'logged_in_username' => $logged_in_username,
                                'logged_in_foto_profil_path' => $logged_in_foto_profil_path,
                                'success' => 'Soal berhasil ditambahkan!'
                            ]);
                            $stmt_insert->close();
                            exit; // 🔥 WAJIB ADA
                        } else {
                            $error = "Gagal menyimpan ke database: " . $stmt_insert->error;
                        }
                        $stmt_insert->close();
                    } else {
                        $error = "Gagal menyiapkan query insert.";
                    }
                }
            }
        }

        echo json_encode([
            'logged_in_admin_name' => $logged_in_admin_name,
            'logged_in_username' => $logged_in_username,
            'logged_in_foto_profil_path' => $logged_in_foto_profil_path,
            'error' => $error ?? 'Terjadi kesalahan tak terduga.'
        ]);
        exit; // 🔥 WAJIB ADA
    }

    // === TAMPILKAN DATA BERDASARKAN id_jurusan DARI URL ===
    $id_jurusan = $_GET['id_jurusan'] ?? null;

    if ($id_jurusan === null) {
        // Tampilkan daftar jurusan
        $stmt = $conn->prepare("SELECT id_jurusan, nama_jurusan FROM jurusan ORDER BY nama_jurusan");
        $stmt->execute();
        $daftar_jurusan = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        ob_start();
        ?>
        <h1 class="page-title"><i class="fas fa-graduation-cap"></i> Pilih Jurusan untuk Manajemen Soal</h1>
        <div class="jurusan-grid">
          <?php foreach ($daftar_jurusan as $j): ?>
            <div class="jurusan-card" onclick="window.location='manajemen_soal.html?id_jurusan=<?= urlencode($j['id_jurusan']) ?>'">
              <div class="jurusan-icon"><i class="fas fa-book-open"></i></div>
              <div class="jurusan-name"><?= htmlspecialchars($j['nama_jurusan'], ENT_QUOTES, 'UTF-8') ?></div>
            </div>
          <?php endforeach; ?>
        </div>
        <?php
        $html = ob_get_clean();
        echo json_encode([
            'logged_in_admin_name' => $logged_in_admin_name,
            'logged_in_username' => $logged_in_username,
            'logged_in_foto_profil_path' => $logged_in_foto_profil_path,
            'html' => $html
        ]);
        exit; // 🔥 WAJIB ADA
    }

    // Tampilkan soal dari jurusan yang dipilih
    $stmt = $conn->prepare("SELECT nama_jurusan FROM jurusan WHERE id_jurusan = ?");
    $stmt->bind_param("s", $id_jurusan);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $jurusan_nama = htmlspecialchars($row['nama_jurusan'], ENT_QUOTES, 'UTF-8');

        $stmt2 = $conn->prepare("
            SELECT id_seleksi, no_soal, soal, opsi_a, opsi_b, opsi_c, opsi_d, 
                   kunci_jawaban, poin, waktu_mulai, waktu_selesai, gambar_soal 
            FROM seleksi 
            WHERE id_jurusan = ? 
            ORDER BY no_soal ASC
        ");
        $stmt2->bind_param("s", $id_jurusan);
        $stmt2->execute();
        $soal_list = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt2->close();

        ob_start();
        ?>
        <a href="manajemen_soal.html" class="btn-secondary" style="margin-bottom:20px; display:inline-flex; align-items:center; gap:6px; text-decoration: none; padding: 8px 16px; border-radius: 6px; background: #6b7280; color: white; font-weight: 500;">
          <i class="fas fa-arrow-left"></i> Kembali ke Daftar Jurusan
        </a>

        <h1 class="page-title">
          <i class="fas fa-book"></i> Manajemen Soal – <?= $jurusan_nama ?>
        </h1>

        <div style="display:flex; gap:12px; flex-wrap:wrap;">
          <button class="btn btn-add" onclick="openModal()">
            <i class="fas fa-plus"></i> Tambah Soal
          </button>
          <button class="btn btn-import" onclick="openImportExcelModal()">
            <i class="fas fa-file-excel"></i> Import Excel
          </button>
        </div>

        <?php if (!empty($soal_list)): ?>
          <div class="action-bar">
            <div class="action-item">
              <i class="far fa-clock"></i> Waktu Mulai:
              <input type="datetime-local" id="waktu_mulai" class="action-input" required>
            </div>
            <div class="action-item">
              Selesai:
              <input type="datetime-local" id="waktu_selesai" class="action-input" required>
            </div>
            <button type="button" class="btn-update-time" onclick="updateWaktuTerpilih()">
              <i class="fas fa-sync-alt"></i> Perbarui Waktu
            </button>
            <button type="button" class="btn-delete-selected" onclick="hapusTerpilih()">
              <i class="fas fa-trash-alt"></i> Hapus Terpilih
            </button>
          </div>

          <div class="table-container">
            <table>
              <thead>
                <tr>
                  <th class="checkbox-cell"><input type="checkbox" id="select-all"></th>
                  <th class="no-cell">No</th>
                  <th>Soal</th>
                  <th>Opsi</th>
                  <th>Kunci</th>
                  <th>Poin</th>
                  <th>Mulai</th>
                  <th>Selesai</th>
                  <th>Gambar</th>
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($soal_list as $soal): ?>
                  <tr data-id-soal="<?= htmlspecialchars($soal['id_seleksi'], ENT_QUOTES) ?>">
                    <td class="checkbox-cell"><input type="checkbox" name="id_soal[]" value="<?= htmlspecialchars($soal['id_seleksi'], ENT_QUOTES) ?>" class="soal-checkbox"></td>
                    <td class="no-cell"><?= (int)$soal['no_soal'] ?></td>
                    <td class="soal-text"><?= htmlspecialchars($soal['soal'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="opsi-text">
                      A. <?= htmlspecialchars($soal['opsi_a'], ENT_QUOTES, 'UTF-8') ?><br>
                      B. <?= htmlspecialchars($soal['opsi_b'], ENT_QUOTES, 'UTF-8') ?><br>
                      C. <?= htmlspecialchars($soal['opsi_c'], ENT_QUOTES, 'UTF-8') ?><br>
                      D. <?= htmlspecialchars($soal['opsi_d'], ENT_QUOTES, 'UTF-8') ?>
                    </td>
                    <td class="kunci-cell"><?= htmlspecialchars($soal['kunci_jawaban'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="poin-cell"><?= (int)$soal['poin'] ?></td>
                    <td class="waktu-cell"><?= $soal['waktu_mulai'] ? date('d/m<br>H:i', strtotime($soal['waktu_mulai'])) : '–' ?></td>
                    <td class="waktu-cell"><?= $soal['waktu_selesai'] ? date('d/m<br>H:i', strtotime($soal['waktu_selesai'])) : '–' ?></td>
                    <td class="gambar-cell">
                      <?php if (!empty($soal['gambar_soal'])): ?>
                        <img src="<?= htmlspecialchars($soal['gambar_soal'], ENT_QUOTES, 'UTF-8') ?>" 
                             alt="Gambar Soal" 
                             style="max-width: 80px; max-height: 80px; object-fit: contain;">
                      <?php else: ?>
                        –
                      <?php endif; ?>
                    </td>
                    <td class="aksi-cell">
                      <a href="edit_soal.php?id=<?= htmlspecialchars($soal['id_seleksi'], ENT_QUOTES) ?>" class="btn-edit" title="Edit Soal">
                        <i class="fas fa-edit"></i>
                      </a>
                      <button type="button"
                              class="btn-hapus-aksi"
                              title="Hapus Soal"
                              onclick="hapusSoal(<?= json_encode($soal['id_seleksi']) ?>, <?= json_encode($id_jurusan) ?>)">
                        <i class="fas fa-trash"></i> Hapus
                      </button>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php else: ?>
          <div class="table-container">
            <p style="text-align:center; padding:32px; color:var(--muted); font-style:italic; animation: fadeInUp 0.6s ease-out;">
              Belum ada soal. Silakan tambahkan soal pertama.
            </p>
          </div>
        <?php endif; ?>
        <?php
        $html = ob_get_clean();
        echo json_encode([
            'logged_in_admin_name' => $logged_in_admin_name,
            'logged_in_username' => $logged_in_username,
            'logged_in_foto_profil_path' => $logged_in_foto_profil_path,
            'html' => $html
        ]);
        exit; // 🔥 WAJIB ADA
    } else {
        echo json_encode([
            'html' => "<div style='padding:40px;text-align:center;color:red;font-weight:bold'>Jurusan tidak ditemukan.</div>"
        ]);
        exit; // 🔥 WAJIB ADA
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'logged_in_admin_name' => $logged_in_admin_name,
        'logged_in_username' => $logged_in_username,
        'logged_in_foto_profil_path' => $logged_in_foto_profil_path,
        'error' => 'Error: ' . $e->getMessage()
    ]);
    exit; // 🔥 WAJIB ADA
}
?>