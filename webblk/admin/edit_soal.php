<?php
ob_start();
session_start();

include '../config/database.php'; 

// Cek otorisasi
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$id_soal = $_GET['id'] ?? null;
if (!$id_soal || !is_numeric($id_soal)) {
    die("Soal tidak ditemukan.");
}
$id_soal = (int)$id_soal;

// Ambil data soal (tanpa opsi_e)
$stmt = $conn->prepare("
    SELECT s.*, j.nama_jurusan, j.id_jurusan 
    FROM seleksi s 
    JOIN jurusan j ON s.id_jurusan = j.id_jurusan 
    WHERE s.id_seleksi = ?
");
$stmt->bind_param("i", $id_soal);
$stmt->execute();
$soal = $stmt->get_result()->fetch_assoc();

if (!$soal) {
    die("Soal tidak ditemukan.");
}

$id_jurusan = $soal['id_jurusan'];
$jurusan_nama = htmlspecialchars($soal['nama_jurusan'], ENT_QUOTES, 'UTF-8');

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ================= DATA FORM (HANYA A-D) =================
    $soal_text = trim($_POST['soal'] ?? '');
    $opsiA = trim($_POST['opsi_a'] ?? '');
    $opsiB = trim($_POST['opsi_b'] ?? '');
    $opsiC = trim($_POST['opsi_c'] ?? '');
    $opsiD = trim($_POST['opsi_d'] ?? '');
    $kunci = $_POST['kunci_jawaban'] ?? '';
    $poin = (int)($_POST['poin'] ?? 10);
    $waktu_mulai = !empty($_POST['waktu_mulai']) ? $_POST['waktu_mulai'] : null;
    $waktu_selesai = !empty($_POST['waktu_selesai']) ? $_POST['waktu_selesai'] : null;

    // ================= VALIDASI (HANYA A-D) =================
    if (
        !$soal_text || !$opsiA || !$opsiB || !$opsiC || !$opsiD ||
        !in_array($kunci, ['A','B','C','D']) || $poin <= 0
    ) {
        $error = "Semua field soal dan opsi (A-D) wajib diisi dengan benar.";
    } elseif ($waktu_mulai && $waktu_selesai && strtotime($waktu_mulai) >= strtotime($waktu_selesai)) {
        $error = "Waktu mulai harus sebelum waktu selesai.";
    }

    // ======================================================
    // ================= PROSES GAMBAR ======================
    // ======================================================
   if (!$error) {

    // gambar lama dari DB
    $gambar_final = $_POST['gambar_lama'] ?? null;
    $hapus_gambar = isset($_POST['hapus_gambar']);

    // ===== PATH KONSISTEN =====
    $upload_dir_server = __DIR__ . '/soal/';
    $upload_dir_url    = 'soal/';

    if (!is_dir($upload_dir_server)) {
        mkdir($upload_dir_server, 0777, true);
    }

    // 1️⃣ UPLOAD GAMBAR BARU (tambah / ganti)
    if (!empty($_FILES['gambar_soal']['name'])) {

        $allowed_ext = ['jpg','jpeg','png','webp'];
        $ext = strtolower(pathinfo($_FILES['gambar_soal']['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, $allowed_ext)) {
            $error = "Format gambar tidak didukung.";
        } else {

            $filename = 'soal_' . uniqid() . '.' . $ext;
            $target = $upload_dir_server . $filename;

            if (move_uploaded_file($_FILES['gambar_soal']['tmp_name'], $target)) {

                // hapus gambar lama
                if ($gambar_final && file_exists(__DIR__ . '/' . $gambar_final)) {
                    unlink(__DIR__ . '/' . $gambar_final);
                }

                // simpan path baru ke DB
                $gambar_final = $upload_dir_url . $filename;

            } else {
                $error = "Gagal upload gambar.";
            }
        }
    }

    // 2️⃣ HAPUS GAMBAR SAJA (tanpa upload baru)
    elseif ($hapus_gambar && $gambar_final) {

        if (file_exists(__DIR__ . '/' . $gambar_final)) {
            unlink(__DIR__ . '/' . $gambar_final);
        }

        $gambar_final = null;
    }
}


    // ================= UPDATE DATABASE (HANYA A-D) =================
    if (!$error) {

        $stmt = $conn->prepare("
            UPDATE seleksi 
            SET soal = ?, opsi_a = ?, opsi_b = ?, opsi_c = ?, opsi_d = ?, 
                kunci_jawaban = ?, poin = ?, waktu_mulai = ?, waktu_selesai = ?, gambar_soal = ?
            WHERE id_seleksi = ?
        ");

        $wm = $waktu_mulai ?: null;
        $ws = $waktu_selesai ?: null;

        $stmt->bind_param(
            "sssssssssi",
            $soal_text, $opsiA, $opsiB, $opsiC, $opsiD,
            $kunci, $poin, $wm, $ws, $gambar_final, $id_soal
        );

        if ($stmt->execute()) {
            header("Location: manajemen_soal.html?id_jurusan=$id_jurusan");
            exit;
        } else {
            $error = "Gagal memperbarui soal.";
        }
    }
}

?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Edit Soal – <?= $jurusan_nama ?></title>
  <!-- 🔥 PERBAIKAN FONT: HAPUS SPASI BERLEBIH -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    :root {
      --primary: #3565A5;
      --text: #1f2937;
      --card: #ffffff;
      --bg: #f1f5f9;
      --shadow: 0 2px 8px rgba(53, 101, 165, 0.15);
      --danger: #ef4444;
    }
    * { margin: 0; padding: 0; box-sizing: border-box; font-family: "Poppins", sans-serif; }
    body { background: var(--bg); color: var(--text); line-height: 1.6; padding-top: 80px; }
    main { padding: 0 24px 40px; max-width: 800px; margin: 0 auto; }
    .card { background: var(--card); padding: 24px; border-radius: 12px; box-shadow: var(--shadow); margin-top: 20px; }
    h1 { font-size: 22px; color: var(--primary); margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
    .form-group { margin-bottom: 16px; }
    label { display: block; margin-bottom: 6px; font-weight: 500; }
    .form-control {
      width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-size: 15px;
    }
    textarea.form-control { min-height: 80px; }
    .btn {
      padding: 10px 20px; border: none; border-radius: 6px; font-weight: 500; cursor: pointer;
      display: inline-flex; align-items: center; gap: 6px;
    }
    .btn-primary { background: var(--primary); color: white; }
    .btn-secondary { 
      background: #6b7280; color: white; text-decoration: none; display: inline-block;
    }
    .alert-error {
      background: #fef2f2; color: var(--danger); padding: 12px; border-radius: 6px;
      margin-bottom: 16px; display: flex; align-items: center; gap: 10px;
    }
    .back-link {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      color: #6b7280;
      text-decoration: none;
      font-weight: 500;
      margin-bottom: 20px;
    }
    .back-link:hover {
      color: var(--primary);
    }
  </style>
</head>
<body>

<main>
  <a href="manajemen_soal.html?id_jurusan=<?= $id_jurusan ?>" class="back-link">
    <i class="fas fa-arrow-left"></i> Kembali ke Daftar Soal
  </a>

  <div class="card">
    <h1><i class="fas fa-edit"></i> Edit Soal – <?= $jurusan_nama ?></h1>

    <?php if ($error): ?>
      <div class="alert-error">
        <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
      </div>
    <?php endif; ?>

   <form method="POST" enctype="multipart/form-data">
      <div class="form-group">
        <label>Pertanyaan</label>
        <textarea name="soal" class="form-control" required><?= htmlspecialchars($soal['soal'], ENT_QUOTES, 'UTF-8') ?></textarea>
      </div>
      
      <!-- ================= GAMBAR SOAL ================= -->
<div class="form-group">
  <label>Gambar Soal (Opsional)</label>

  <?php if (!empty($soal['gambar_soal'])): ?>
    <div style="margin-bottom:10px">
      <img src="<?= htmlspecialchars($soal['gambar_soal']) ?>" style="max-width:150px; display:block; margin-bottom:6px;">
      <label>
        <input type="checkbox" name="hapus_gambar" value="1">
        Hapus gambar
      </label>
    </div>
  <?php endif; ?>

  <input type="file" name="gambar_soal" accept="image/*" class="form-control">

  <input type="hidden" name="gambar_lama" value="<?= htmlspecialchars($soal['gambar_soal'] ?? '') ?>">
</div>


      <div class="form-group">
        <label>Opsi A</label>
        <input type="text" name="opsi_a" class="form-control" value="<?= htmlspecialchars($soal['opsi_a'], ENT_QUOTES, 'UTF-8') ?>" required>
      </div>
      <div class="form-group">
        <label>Opsi B</label>
        <input type="text" name="opsi_b" class="form-control" value="<?= htmlspecialchars($soal['opsi_b'], ENT_QUOTES, 'UTF-8') ?>" required>
      </div>
      <div class="form-group">
        <label>Opsi C</label>
        <input type="text" name="opsi_c" class="form-control" value="<?= htmlspecialchars($soal['opsi_c'], ENT_QUOTES, 'UTF-8') ?>" required>
      </div>
      <div class="form-group">
        <label>Opsi D</label>
        <input type="text" name="opsi_d" class="form-control" value="<?= htmlspecialchars($soal['opsi_d'], ENT_QUOTES, 'UTF-8') ?>" required>
      </div>

      <div class="form-group">
        <label>Kunci Jawaban</label>
        <select name="kunci_jawaban" class="form-control" required>
          <option value="A" <?= $soal['kunci_jawaban'] === 'A' ? 'selected' : '' ?>>A</option>
          <option value="B" <?= $soal['kunci_jawaban'] === 'B' ? 'selected' : '' ?>>B</option>
          <option value="C" <?= $soal['kunci_jawaban'] === 'C' ? 'selected' : '' ?>>C</option>
          <option value="D" <?= $soal['kunci_jawaban'] === 'D' ? 'selected' : '' ?>>D</option>
        </select>
      </div>

      <div class="form-group">
        <label>Poin</label>
        <input type="number" name="poin" class="form-control" value="<?= (int)$soal['poin'] ?>" min="1" required>
      </div>

      <div class="form-group">
        <label>Waktu Mulai</label>
        <input type="datetime-local" name="waktu_mulai" class="form-control" 
               value="<?= $soal['waktu_mulai'] ? date('Y-m-d\TH:i', strtotime($soal['waktu_mulai'])) : '' ?>">
      </div>

      <div class="form-group">
        <label>Waktu Selesai</label>
        <input type="datetime-local" name="waktu_selesai" class="form-control"
               value="<?= $soal['waktu_selesai'] ? date('Y-m-d\TH:i', strtotime($soal['waktu_selesai'])) : '' ?>">
      </div>

      <button type="submit" class="btn btn-primary">
        <i class="fas fa-save"></i> Simpan Perubahan
      </button>
    </form>
  </div>
</main>

</body>
</html>