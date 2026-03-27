<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Koreksi Jawaban – e-BLK Nganjuk</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    :root {
      --primary: #3565A5;
      --primary-light: #4a7bbd;
      --text: #1f2937;
      --muted: #6b7280;
      --card: #ffffff;
      --bg: #f1f5f9;
      --shadow: 0 4px 12px rgba(53, 101, 165, 0.08);
      --shadow-hover: 0 8px 20px rgba(53, 101, 165, 0.15);
      --success: #10b981;
      --danger: #ef4444;
      --warning: #f59e0b;
      --transition: all 0.35s cubic-bezier(0.23, 1, 0.32, 1);
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: "Poppins", sans-serif;
    }

    body {
      background: var(--bg);
      color: var(--text);
      line-height: 1.6;
      padding-top: 80px;
      overflow-x: hidden;
    }

    .hero {
      min-height: 160px;
      padding-top: 40px;
      background: linear-gradient(135deg, var(--primary), #2c5282);
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      text-align: center;
      flex-direction: column;
      padding: 0 16px;
      position: relative;
      z-index: 1;
    }

    .hero h1 {
      font-size: 28px;
      margin-bottom: 8px;
      position: relative;
      z-index: 2;
    }

    main {
      padding: 30px 24px 40px;
    }

    .container {
      max-width: 1200px;
      margin: 0 auto;
    }

    .btn {
      padding: 10px 18px;
      border: none;
      border-radius: 10px;
      cursor: pointer;
      font-weight: 600;
      font-size: 14px;
      transition: var(--transition);
      display: inline-flex;
      align-items: center;
      gap: 8px;
    }

    .btn-back {
      background: var(--primary);
      color: white;
      margin-bottom: 20px;
    }

    .btn-lulus {
      background: var(--success);
      color: white;
    }

    .btn-tolak {
      background: var(--danger);
      color: white;
    }

    .data-table {
      width: 100%;
      background: white;
      border-radius: 16px;
      overflow: hidden;
      box-shadow: var(--shadow);
      margin-top: 16px;
    }

    table { width: 100%; border-collapse: collapse; }
    th, td { padding: 14px 16px; text-align: left; border-bottom: 1px solid #eee; }
    th { background: var(--primary); color: white; font-weight: 600; }
    tr:last-child td { border-bottom: none; }
    tr:hover { background: #f8fafc; }

    .status-benar { color: var(--success); font-weight: bold; }
    .status-salah { color: var(--danger); font-weight: bold; }

    .aksi-cell {
      display: flex;
      gap: 8px;
    }

    .loading {
      text-align: center;
      padding: 40px;
      color: var(--muted);
    }

    /* Modal */
    #modal-detail {
      display: none;
      position: fixed;
      top: 0; left: 0;
      width: 100%; height: 100%;
      background: rgba(0,0,0,0.6);
      z-index: 9999;
      justify-content: center;
      align-items: center;
      padding: 20px;
    }

    .modal-content {
      background: white;
      border-radius: 16px;
      max-width: 900px;
      width: 100%;
      max-height: 85vh;
      overflow-y: auto;
      padding: 24px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.3);
    }

    .modal-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
      padding-bottom: 12px;
      border-bottom: 2px solid #eee;
    }

    .modal-actions {
      margin-top: 20px;
      text-align: right;
    }

    @media (max-width: 768px) {
      .hero h1 { font-size: 22px; }
      .btn { width: 100%; justify-content: center; margin-bottom: 10px; }
      .aksi-cell { flex-direction: column; }
    }
    
    /* Detail jadwal */
    .soal-card {
      border: 1px solid #ddd;
      border-radius: 8px;
      padding: 16px;
      margin-bottom: 20px;
      background: #fff;
    }

    .soal-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .soal-poin {
      background: #f1f1f1;
      padding: 4px 10px;
      border-radius: 6px;
      font-size: 13px;
    }

    .opsi-list {
      margin-top: 12px;
    }

    .opsi-item {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 6px 10px;
      border-radius: 6px;
    }

    .opsi-kunci {
      background: #e8f5e9;
      border: 1px solid #4caf50;
    }

    .opsi-salah {
      background: #fdecea;
      border: 1px solid #f44336;
    }

    .label-kunci {
      margin-left: auto;
      color: #4caf50;
      font-weight: bold;
    }

    .jawaban-status {
      margin-top: 10px;
      font-weight: bold;
    }

    .jawaban-status.benar {
      color: #2e7d32;
    }

    .jawaban-status.salah {
      color: #c62828;
    }
  </style>
</head>
<body>

<div id="header-container"></div>

<section class="hero">
  <h1>🔍 Koreksi Jawaban Peserta</h1>
  <p>Peserta yang sudah tes namun belum ditentukan kelulusannya</p>
</section>

<main>
  <div class="container">
    <button class="btn btn-back" onclick="window.location.href='lihat_data_peserta.html'">
      <i class="fas fa-arrow-left"></i> Kembali ke Menu
    </button>
    
    <!-- Ganti bagian filter-bar lama dengan ini -->
<div class="filter-bar" style="margin: 20px 0; display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
  <label for="filter-jurusan" style="font-weight: 600; color: var(--text); white-space: nowrap;">Filter Jurusan:</label>
  <div class="select-wrapper" style="position: relative; min-width: 200px; width: auto;">
    <select id="filter-jurusan" 
            style="width: 100%; padding: 12px 16px; border: 2px solid #e2e8f0; border-radius: 12px; 
                   background: white; font-family: 'Poppins', sans-serif; font-size: 14px; 
                   color: var(--text); cursor: pointer; transition: var(--transition);
                   appearance: none; padding-right: 40px;"
            onfocus="this.style.borderColor='#3565A5'" 
            onblur="this.style.borderColor='#e2e8f0'">
      <option value="">Semua Jurusan</option>
    </select>
    <!-- Ikon panah custom -->
    <div style="position: absolute; right: 16px; top: 50%; transform: translateY(-50%); pointer-events: none;">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M6 9L12 15L18 9" stroke="#6b7280" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
    </div>
  </div>
</div>

    <div id="content-placeholder">
      <div class="loading">
        <i class="fas fa-spinner fa-spin"></i> Memuat daftar peserta...
      </div>
    </div>
  </div>
</main>

<!-- Modal Detail -->
<div id="modal-detail">
  <div class="modal-content">
    <div class="modal-header">
      <h2 id="modal-title">Detail Jawaban</h2>
      <button onclick="closeModal()" style="background:none; border:none; font-size:24px; cursor:pointer;">×</button>
    </div>
    <div id="modal-body"></div>
    <div class="modal-actions">
      <button class="btn btn-tolak" onclick="tetapkanStatus('tidak_lulus')">
        <i class="fas fa-times"></i> Tidak Lulus (Hapus Data)
      </button>
      <button class="btn btn-lulus" onclick="tetapkanStatus('lulus')">
        <i class="fas fa-check"></i> Tetapkan Lulus
      </button>
    </div>
  </div>
</div>

<div id="footer-container"></div>

<script>
let currentIdPeserta = null;
let allPesertaData = []; // ✅ Simpan data mentah untuk filtering

function showToast(message, type = 'info') {
  const toast = document.createElement('div');
  toast.innerHTML = message;
  toast.style.cssText = `
    position: fixed; bottom: 20px; right: 20px; padding: 12px 20px;
    background: ${type === 'error' ? '#ef4444' : type === 'success' ? '#10b981' : '#3b82f6'};
    color: white; border-radius: 8px; z-index: 10000;
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
  `;
  document.body.appendChild(toast);
  setTimeout(() => toast.remove(), 3000);
}

// ✅ MUAT DAFTAR JURUSAN DARI API
async function loadJurusanOptions() {
  try {
    const res = await fetch('lihat_data_peserta.php?mode=get_jurusan');
    const data = await res.json();
    
    if (!data.success) throw new Error(data.error || 'Gagal muat jurusan');

    const select = document.getElementById('filter-jurusan');
    select.innerHTML = '<option value="">Semua Jurusan</option>';
    
    data.data.forEach(j => {
      const opt = document.createElement('option');
      opt.value = j.id_jurusan;
      opt.textContent = j.nama_jurusan;
      select.appendChild(opt);
    });

    select.addEventListener('change', applyJurusanFilter);
  } catch (err) {
    console.error('Error muat jurusan:', err);
    showToast('Gagal memuat daftar jurusan', 'error');
  }
}

// ✅ FILTER TABEL BERDASARKAN JURUSAN
function applyJurusanFilter() {
  const id = document.getElementById('filter-jurusan').value;
  const filtered = id 
    ? allPesertaData.filter(p => p.id_jurusan == id)
    : allPesertaData;
  renderPesertaTable(filtered);
}

// ✅ RENDER TABEL
function renderPesertaTable(list) {
  let html = `<div class="data-table">
    <table>
      <thead>
        <tr>
          <th>No</th>
          <th>Nama</th>
          <th>NIK</th>
          <th>Jurusan</th>
          <th>Poin</th>
          <th>Benar/Salah</th>
          <th>Tanggal Tes</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>`;

  if (list.length === 0) {
    html += `<tr><td colspan="8" style="text-align:center; padding:30px; color:#888;">Tidak ada peserta yang perlu dikoreksi.</td></tr>`;
  } else {
    list.forEach((p, index) => {
      html += `
        <tr>
          <td>${index + 1}</td>
          <td>${p.nama_peserta}</td>
          <td>${p.NIK}</td>
          <td>${p.nama_jurusan}</td>
          <td>${p.total_poin}</td>
          <td>${p.total_benar} / ${p.total_salah}</td>
          <td>${p.tanggal_tes}</td>
          <td class="aksi-cell">
            <button class="btn btn-lulus" onclick="lihatJawaban(${p.id_peserta})">
              <i class="fas fa-eye"></i> Lihat Jawaban
            </button>
          </td>
        </tr>`;
    });
  }

  html += `</tbody></table></div>`;
  document.getElementById('content-placeholder').innerHTML = html;
}

// ✅ Inisialisasi: ambil header/footer + data profil
async function init() {
  try {
    const [headerRes, footerRes] = await Promise.all([
      fetch('includes/header.html'),
      fetch('includes/footer.html')
    ]);
    document.getElementById('header-container').innerHTML = await headerRes.text();
    document.getElementById('footer-container').innerHTML = await footerRes.text();

    const userRes = await fetch('dashboard_admin_api.php');
    if (userRes.ok) {
      const userData = await userRes.json();
      const avatar = document.querySelector('#header-container img[src*="profile"]');
      if (avatar) {
        avatar.src = userData.logged_in_foto_profil_path || 'images/profile.png';
        avatar.onerror = () => avatar.src = 'images/profile.png';
      }

      const usernameSelectors = ['#header-username', '.logged-in-username', '.username', 'span[title="Username"]', 'li a span'];
      let usernameEl = null;
      for (const sel of usernameSelectors) {
        usernameEl = document.querySelector(sel);
        if (usernameEl) break;
      }
      if (usernameEl) {
        usernameEl.textContent = userData.logged_in_username || 'Admin';
      }
    }

    // ✅ MUAT JURUSAN & PESERTA
    await loadJurusanOptions();
    await fetchPesertaList();
  } catch (err) {
    console.warn('Gagal muat header/footer/user:', err);
  }
}

// ✅ Ambil daftar peserta
async function fetchPesertaList() {
  try {
    const res = await fetch('lihat_data_peserta.php?mode=koreksi');
    const data = await res.json();
    
    if (!data.success || data.type !== 'list') {
      throw new Error(data.error || 'Gagal memuat data');
    }

    allPesertaData = data.peserta_koreksi; // simpan data mentah
    renderPesertaTable(allPesertaData);
  } catch (err) {
    console.error(err);
    document.getElementById('content-placeholder').innerHTML = 
      `<div class="loading" style="color:red;">Gagal memuat: ${err.message}</div>`;
  }
}

// ✅ Lihat detail jawaban
async function lihatJawaban(idPeserta) {
  currentIdPeserta = idPeserta;
  try {
    const res = await fetch(`lihat_data_peserta.php?mode=koreksi&id_peserta=${idPeserta}`);
    const data = await res.json();

    if (!data.success || data.type !== 'detail') {
      throw new Error(data.error || 'Gagal memuat detail');
    }

    let html = `
      <h3>${data.peserta.nama_peserta} — ${data.peserta.nama_jurusan}</h3>
      <p>
        <strong>Total:</strong> ${data.peserta.total_poin} poin |
        Benar: ${data.peserta.total_benar} |
        Salah: ${data.peserta.total_salah}
      </p>
      <p><strong>Tanggal Tes:</strong> ${data.peserta.tanggal_tes}</p>
      <hr style="margin:20px 0;">
    `;

    data.jawaban.forEach(j => {
      html += `
        <div class="soal-card">
          <div class="soal-header">
            <div class="soal-text">
              <strong>${j.no_soal}. ${j.soal}</strong>
            </div>
            <div class="soal-poin">
              ${j.poin} poin
            </div>
          </div>
          <div class="opsi-list">`;

      for (const key in j.opsi) {
        const isJawabanPeserta = key === j.jawaban;
        const isKunci = key === j.kunci_jawaban;
        let classOpsi = 'opsi-item';
        if (isKunci) classOpsi += ' opsi-kunci';
        if (isJawabanPeserta && !j.benar_salah) classOpsi += ' opsi-salah';

        html += `
          <div class="${classOpsi}">
            <input type="radio" ${isJawabanPeserta ? 'checked' : ''} disabled>
            <span><strong>${key}.</strong> ${j.opsi[key]}</span>
            ${isKunci ? '<span class="label-kunci">✔</span>' : ''}
          </div>`;
      }

      html += `
          </div>
          <div class="jawaban-status ${j.benar_salah ? 'benar' : 'salah'}">
            Jawaban Peserta: <strong>${j.jawaban}</strong> 
            (${j.benar_salah ? 'Benar' : 'Salah'})
          </div>
        </div>`;
    });

    document.getElementById('modal-body').innerHTML = html;
    document.getElementById('modal-detail').style.display = 'flex';
  } catch (err) {
    showToast('Gagal memuat jawaban: ' + err.message, 'error');
  }
}

function closeModal() {
  document.getElementById('modal-detail').style.display = 'none';
  currentIdPeserta = null;
}

// ✅ Tetapkan status kelulusan
async function tetapkanStatus(status) {
  if (!currentIdPeserta) return;
  
  if (status === 'tidak_lulus') {
    if (!confirm('Yakin hapus data peserta ini? Tindakan tidak bisa dibatalkan.')) return;
  }

  try {
    const res = await fetch(`tetapkan_status.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `id_peserta=${currentIdPeserta}&status=${status}`
    });
    const result = await res.json();
    
    if (result.success) {
      showToast(result.message, 'success');
      closeModal();
      fetchPesertaList(); // Refresh daftar
    } else {
      showToast(result.error || 'Gagal menetapkan status', 'error');
    }
  } catch (err) {
    showToast('Error: ' + err.message, 'error');
  }
}

document.addEventListener('DOMContentLoaded', init);

// Tutup modal saat klik luar
document.getElementById('modal-detail').addEventListener('click', (e) => {
  if (e.target.id === 'modal-detail') closeModal();
});
</script>
</body>
</html>