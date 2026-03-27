<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Semua Data Peserta – e-BLK Nganjuk</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    :root {
      --primary: #3565A5;
      --primary-light: #4a7bbd;
      --primary-soft: rgba(53, 101, 165, 0.12);
      --text: #1f2937;
      --muted: #6b7280;
      --card: #ffffff;
      --bg: #f1f5f9;
      --shadow: 0 4px 12px rgba(53, 101, 165, 0.08);
      --shadow-hover: 0 8px 20px rgba(53, 101, 165, 0.15);
      --success: #10b981;
      --info: #3b82f6;
      --warning: #f59e0b;
      --danger: #ef4444;
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

    /* === TOMBOL KEMBALI — DI DALAM FLOW === */
    .back-button {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      background: var(--primary);
      color: white;
      border: none;
      border-radius: 10px;
      padding: 10px 16px;
      font-size: 14px;
      font-weight: 600;
      cursor: pointer;
      box-shadow: 0 4px 8px rgba(0,0,0,0.15);
      transition: var(--transition);
      margin: 20px 0 24px 24px; /* Jarak dari kiri & bawah */
    }

    .back-button:hover {
      background: var(--primary-light);
      transform: translateY(-2px);
      box-shadow: 0 6px 12px rgba(0,0,0,0.2);
    }

    .hero {
      min-height: 200px;
      padding-top: 60px;
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

    .hero::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: url('images/blk.jpeg') center/cover no-repeat;
      opacity: 0.1;
      z-index: -1;
    }

    .hero h1 {
      font-size: 32px;
      margin-bottom: 8px;
      position: relative;
      z-index: 2;
    }

    .hero p {
      font-size: 16px;
      max-width: 600px;
      position: relative;
      z-index: 2;
    }

    main {
      padding: 0 24px 40px;
    }

    .container {
      max-width: 1400px;
      margin: 0 auto;
    }

    h2 {
      font-size: 22px;
      color: var(--primary);
      margin: 24px 0 16px;
      position: relative;
      opacity: 0;
      transform: translateY(10px);
      animation: fadeIn 0.5s forwards 0.1s;
    }

    h2::after {
      content: '';
      position: absolute;
      bottom: -6px;
      left: 0;
      width: 40px;
      height: 3px;
      background: var(--primary);
      border-radius: 2px;
    }

    @keyframes fadeIn {
      to { opacity: 1; transform: translateY(0); }
    }

    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
      gap: 20px;
      margin-bottom: 32px;
    }

    .stat-card {
      background: white;
      border-radius: 16px;
      padding: 22px;
      box-shadow: var(--shadow);
      transition: var(--transition);
      cursor: default;
      position: relative;
      overflow: hidden;
      opacity: 0;
      transform: translateY(20px);
    }

    .stat-card.visible {
      opacity: 1;
      transform: translateY(0);
    }

    .stat-card:hover {
      transform: translateY(-8px);
      box-shadow: var(--shadow-hover);
    }

    .stat-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 5px;
      background: linear-gradient(90deg, var(--primary), var(--primary-light));
      border-radius: 16px 16px 0 0;
    }

    .stat-card h3 {
      font-size: 16px;
      color: var(--text);
      margin-bottom: 16px;
      font-weight: 600;
      text-align: center;
    }

    .stat-row {
      display: flex;
      justify-content: space-around;
      margin-top: 12px;
      flex-wrap: wrap;
      gap: 10px;
    }

    .stat-item {
      text-align: center;
      min-width: 65px;
      padding: 10px;
      border-radius: 12px;
      background: #f8fafc;
      transition: var(--transition);
    }

    .stat-item:hover {
      background: var(--primary-soft);
      transform: scale(1.05);
    }

    .stat-label {
      font-size: 12px;
      color: var(--muted);
      margin-bottom: 4px;
      font-weight: 500;
    }

    .stat-value {
      font-weight: 600;
      font-size: 18px;
    }

    .stat-seleksi { color: var(--info); }
    .stat-aktif { color: var(--success); }
    .stat-drop { color: var(--danger); }
    .stat-lulus { color: var(--warning); }

    /* 🔸 CUSTOM DROPDOWN & INPUT STYLE */
    .search-section,
    .controls-group {
      display: flex;
      gap: 16px;
      margin-bottom: 24px;
      flex-wrap: wrap;
      align-items: center;
      background: white;
      padding: 22px;
      border-radius: 20px;
      box-shadow: var(--shadow);
      opacity: 0;
      transform: translateY(15px);
      animation: fadeIn 0.5s forwards 0.2s;
    }

    .controls-group {
      animation-delay: 0.3s;
    }

    .search-input {
      flex: 1;
      min-width: 220px;
      padding: 14px 20px;
      border: 2px solid #e2e8f0;
      border-radius: 14px;
      font-size: 16px;
      outline: none;
      background: #fafcff;
      transition: var(--transition);
    }

    .search-input:focus {
      border-color: var(--primary);
      box-shadow: 0 0 0 4px rgba(53, 101, 165, 0.15);
    }

    /* 🔸 CUSTOM SELECT STYLE */
    .custom-select {
      position: relative;
      min-width: 200px;
    }

    .custom-select select {
      appearance: none;
      background: #fafcff;
      padding: 14px 48px 14px 20px;
      border: 2px solid #e2e8f0;
      border-radius: 14px;
      font-size: 16px;
      color: var(--text);
      width: 100%;
      cursor: pointer;
      transition: var(--transition);
      outline: none;
    }

    .custom-select select:focus {
      border-color: var(--primary);
      box-shadow: 0 0 0 4px rgba(53, 101, 165, 0.15);
    }

    .custom-select::after {
      content: '\f078';
      font-family: "Font Awesome 6 Free";
      font-weight: 900;
      position: absolute;
      top: 50%;
      right: 18px;
      transform: translateY(-50%);
      color: var(--muted);
      pointer-events: none;
      transition: var(--transition);
    }

    .custom-select:hover::after {
      color: var(--primary);
    }

    .btn {
      padding: 14px 24px;
      color: white;
      border: none;
      border-radius: 14px;
      cursor: pointer;
      font-weight: 600;
      font-size: 16px;
      transition: var(--transition);
      display: flex;
      align-items: center;
      gap: 10px;
      white-space: nowrap;
      box-shadow: 0 4px 10px rgba(53, 101, 165, 0.15);
    }

    .btn-download {
      background: linear-gradient(135deg, #10b981, #0da26f);
    }
    .btn-excel {
      background: linear-gradient(135deg, #3b82f6, #2563eb);
    }
    .btn-pdf {
      background: linear-gradient(135deg, #ef4444, #dc2626);
    }

    .btn-download:hover,
    .btn-excel:hover,
    .btn-pdf:hover {
      transform: translateY(-3px);
      box-shadow: 0 6px 16px rgba(0,0,0,0.2);
    }

    .data-table {
      width: 100%;
      background: white;
      border-radius: 16px;
      overflow: hidden;
      box-shadow: var(--shadow);
      opacity: 0;
      transform: translateY(20px);
      animation: fadeIn 0.5s forwards 0.4s;
    }

    table {
      width: 100%;
      border-collapse: collapse;
    }

    th, td {
      padding: 16px 20px;
      text-align: left;
      border-bottom: 1px solid #e5e7eb;
    }

    th {
      background: linear-gradient(135deg, var(--primary), var(--primary-light));
      color: white;
      font-weight: 600;
      position: sticky;
      top: 0;
      z-index: 10;
    }

    tr:last-child td { border-bottom: none; }
    tr:nth-child(even) { background: #fafcff; }
    tr:hover {
      background: #f0f7ff !important;
      transform: scale(1.005);
      transition: transform 0.2s ease;
    }

    .status-seleksi { color: var(--info); font-weight: 600; }
    .status-aktif { color: var(--success); font-weight: 600; }
    .status-drop { color: var(--danger); font-weight: 600; }
    .status-lulus { color: var(--warning); font-weight: 600; }

    .rank-blue {
      color: var(--primary);
      font-weight: bold;
    }

    @media (max-width: 768px) {
      .hero h1 { font-size: 26px; }
      .stats-grid { grid-template-columns: 1fr; }
      .search-section,
      .controls-group {
        flex-direction: column;
        align-items: stretch;
      }
      .search-input,
      .custom-select {
        width: 100%;
      }
      .custom-select select {
        padding-right: 40px;
      }
      .btn {
        width: 100%;
        justify-content: center;
      }
      .back-button {
        margin: 16px 0 20px 16px;
        padding: 8px 12px;
        font-size: 13px;
      }
    }

    /* === MODERN PAGINATION WITH INPUT === */
    .pagination-controls {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 16px;
      margin-top: 28px;
      flex-wrap: wrap;
    }

    .pagination-btn-round {
      width: 44px;
      height: 44px;
      border-radius: 50%;
      background: var(--primary);
      color: white;
      border: none;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: var(--transition);
      box-shadow: 0 4px 10px rgba(53, 101, 165, 0.2);
      font-size: 16px;
    }

    .pagination-btn-round:hover:not(:disabled) {
      background: var(--primary-light);
      transform: translateY(-2px);
      box-shadow: 0 6px 16px rgba(53, 101, 165, 0.3);
    }

    .pagination-btn-round:disabled {
      opacity: 0.5;
      cursor: not-allowed;
      transform: none;
    }

    .pagination-input {
      width: 60px;
      padding: 8px 12px;
      border: 2px solid #cbd5e1;
      border-radius: 8px;
      text-align: center;
      font-weight: 600;
      transition: var(--transition);
    }

    .pagination-input:focus {
      border-color: var(--primary);
      box-shadow: 0 0 0 4px rgba(53, 101, 165, 0.15);
    }

    .pagination-go-btn {
      padding: 8px 12px;
      background: var(--primary);
      color: white;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      font-size: 14px;
      transition: var(--transition);
    }

    .pagination-go-btn:hover {
      background: var(--primary-light);
    }
  </style>
</head>
<body>

<div id="header-container"></div>

<section class="hero">
  <h1>📊 Semua Data Peserta</h1>
  <p>Ringkasan & daftar lengkap peserta per status</p>
</section>

<!-- 🔹 TOMBOL KEMBALI DI ATAS KOTAK-KOTAK STATISTIK -->
<button class="back-button" onclick="window.location.href='lihat_data_peserta.html'">
  <i class="fas fa-arrow-left"></i> Kembali ke Menu
</button>

<main>
  <div class="container">
    <div id="content-placeholder">
      <h2 style="text-align: center; padding: 40px; color: var(--muted);">
        <i class="fas fa-spinner fa-spin"></i> Memuat data...
      </h2>
    </div>
  </div>
</main>

<div id="footer-container"></div>

<script>
  // ... (SEMUA JAVASCRIPT SAMA SEPERTI SEBELUMNYA) ...
  // Tidak ada perubahan pada JS
  let currentFilter = 'all';
  let currentLimit = 20;
  let currentPage = 1;
  let itemsPerPage = 10;
  let fullData = null;

  function searchGlobal(query) {
    const q = query.toLowerCase().trim();
    const rows = document.querySelectorAll('#dataTable tbody tr');
    rows.forEach(row => {
      const text = row.textContent.toLowerCase();
      const matchesFilter = (currentFilter === 'all' || row.dataset.jurusan === currentFilter);
      row.style.display = (text.includes(q) && matchesFilter) ? '' : 'none';
    });
  }

  function filterGlobal(jurusan) {
    currentFilter = jurusan;
    currentPage = 1;
    renderTablePage();
  }

  function downloadPDFRanking() {
    const scope = document.getElementById('rankingScope').value;
    let url = `lihat_data_peserta.php?mode=pdf&limit=${currentLimit}`;
    if (scope !== 'global') {
      url += '&jurusan=' + encodeURIComponent(scope.replace(/\s+/g, '_'));
    }
    window.open(url, '_blank');
  }

  function downloadExcelRanking() {
    const scope = document.getElementById('rankingScope').value;
    let url = `lihat_data_peserta.php?download=excel_ranking&limit=${currentLimit}`;
    if (scope !== 'global') {
      url += '&jurusan=' + encodeURIComponent(scope.replace(/\s+/g, '_'));
    }
    window.open(url, '_blank');
  }

  function downloadLaporanPDF() {
    const jurusan = document.getElementById('filterJurusan')?.value || 'all';
    let url = 'lihat_data_peserta.php?download=pdf';
    if (jurusan !== 'all') {
      url += '&jurusan=' + encodeURIComponent(jurusan);
    }
    window.open(url, '_blank');
  }

  function goToPage(page) {
    const filteredCount = currentFilter === 'all'
      ? fullData.peserta.length
      : fullData.peserta.filter(p => p.nama_jurusan === currentFilter).length;
    const totalPages = Math.ceil(filteredCount / itemsPerPage);
    if (page >= 1 && page <= totalPages) {
      currentPage = page;
      renderTablePage();
    }
  }

  function renderPaginationHTML(totalPages) {
    if (totalPages <= 1) return '';

    const prevDisabled = currentPage === 1 ? 'disabled' : '';
    const nextDisabled = currentPage === totalPages ? 'disabled' : '';

    return `
      <div class="pagination-controls">
        <button class="pagination-btn-round" ${prevDisabled} onclick="goToPage(${currentPage - 1})">
          <i class="fas fa-chevron-left"></i>
        </button>

        <span style="font-weight:600; color:var(--text);">Halaman</span>

        <input 
          type="number" 
          class="pagination-input" 
          min="1" 
          max="${totalPages}" 
          value="${currentPage}" 
          onkeypress="if(event.key === 'Enter') goToPage(parseInt(this.value))" 
          onchange="if(this.value > 0 && this.value <= ${totalPages}) goToPage(parseInt(this.value))"
        >

        <span style="font-weight:600; color:var(--text);">dari ${totalPages}</span>

        <button class="pagination-go-btn" onclick="goToPage(parseInt(document.querySelector('.pagination-input').value))">
          Go
        </button>

        <button class="pagination-btn-round" ${nextDisabled} onclick="goToPage(${currentPage + 1})">
          <i class="fas fa-chevron-right"></i>
        </button>
      </div>
    `;
  }

  function renderTablePage() {
    if (!fullData) return;

    let filteredPeserta = fullData.peserta;
    if (currentFilter !== 'all') {
      filteredPeserta = filteredPeserta.filter(p => p.nama_jurusan === currentFilter);
    }

    const totalPages = Math.ceil(filteredPeserta.length / itemsPerPage);
    const start = (currentPage - 1) * itemsPerPage;
    const end = start + itemsPerPage;
    const pageData = filteredPeserta.slice(start, end);

    let tableHTML = `
      <h2>📋 Daftar Semua Peserta</h2>
      <div class="search-section">
        <input type="text" id="globalSearch" class="search-input" placeholder="Cari nama, jurusan..." onkeyup="searchGlobal(this.value)">
        
        <div class="custom-select">
          <select onchange="filterGlobal(this.value)" id="filterJurusan">
            <option value="all">Semua Jurusan</option>
    `;

    const jurusanSet = new Set();
    fullData.peserta.forEach(p => {
      if (p.nama_jurusan) jurusanSet.add(p.nama_jurusan);
    });
    const sortedJurusan = Array.from(jurusanSet).sort();

    sortedJurusan.forEach(jur => {
      tableHTML += `<option value="${jur}">${jur}</option>`;
    });

    tableHTML += `
          </select>
        </div>
        <button class="btn btn-download" onclick="downloadLaporanPDF()">
          <i class="fas fa-file-pdf"></i> Unduh Laporan Lengkap
        </button>
      </div>

      <div class="controls-group">
        <div class="custom-select">
          <select id="rankingScope">
            <option value="global">Ranking Global</option>
    `;

    sortedJurusan.forEach(jur => {
      tableHTML += `<option value="${jur}">${jur}</option>`;
    });

    tableHTML += `
          </select>
        </div>

        <button class="btn btn-pdf" onclick="downloadPDFRanking()">
          <i class="fas fa-file-pdf"></i> Cetak PDF Ranking
        </button>
        <button class="btn btn-excel" onclick="downloadExcelRanking()">
          <i class="fas fa-file-excel"></i> Cetak Excel Ranking
        </button>
      </div>

      <div class="data-table">
        <table id="dataTable">
          <thead>
            <tr>
              <th>No</th>
              <th>Ranking</th>
              <th>Nama</th>
              <th>Jurusan</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
    `;

    pageData.forEach((p, idx) => {
      const globalIdx = filteredPeserta.indexOf(p);
      const jurusan = p.nama_jurusan || 'Tidak Ada';
      const isSeleksi = p.status === 'seleksi';
      const globalRank = fullData.ranking_global[p.id_peserta] || null;
      const jurusanRank = fullData.ranking_per_jurusan[jurusan]?.[p.id_peserta] || null;

      let statusClass = '', statusText = p.status;
      if (p.status === 'aktif') { statusClass = 'status-aktif'; statusText = 'Aktif'; }
      else if (p.status === 'seleksi') { statusClass = 'status-seleksi'; statusText = 'Seleksi'; }
      else if (p.status === 'drop_out') { statusClass = 'status-drop'; statusText = 'Drop Out'; }
      else if (p.status === 'lulus') { statusClass = 'status-lulus'; statusText = 'Lulus'; }

      tableHTML += `
        <tr 
          data-jurusan="${jurusan}" 
          data-status="${p.status}"
          data-id-peserta="${p.id_peserta}"
          data-global-rank="${globalRank || ''}"
          data-jurusan-rank="${jurusanRank || ''}"
        >
          <td>${globalIdx + 1}</td>
          <td class="${isSeleksi && globalRank && parseInt(globalRank) <= currentLimit ? 'rank-blue' : ''}">
            ${isSeleksi ? (globalRank || '—') : ''}
          </td>
          <td>${p.nama_peserta}</td>
          <td>${jurusan}</td>
          <td><span class="${statusClass}">${statusText}</span></td>
        </tr>
      `;
    });

    tableHTML += `</tbody></table></div>`;
    tableHTML += renderPaginationHTML(totalPages);

    document.getElementById('content-placeholder').innerHTML = 
      document.querySelector('.stats-grid').outerHTML + tableHTML;

    const searchInput = document.getElementById('globalSearch');
    if (searchInput) {
      searchInput.onkeyup = (e) => searchGlobal(e.target.value);
    }
  }

  function renderStats(data) {
    let statsHTML = '<h2>📈 Ringkasan Peserta per Jurusan</h2><div class="stats-grid">';
    for (const [jurusan, stats] of Object.entries(data.statistik)) {
      statsHTML += `
        <div class="stat-card">
          <h3>${jurusan}</h3>
          <div class="stat-row">
            <div class="stat-item">
              <div class="stat-label">Seleksi</div>
              <div class="stat-value stat-seleksi">${stats.seleksi}</div>
            </div>
            <div class="stat-item">
              <div class="stat-label">Aktif</div>
              <div class="stat-value stat-aktif">${stats.aktif}</div>
            </div>
            <div class="stat-item">
              <div class="stat-label">Drop Out</div>
              <div class="stat-value stat-drop">${stats.drop_out}</div>
            </div>
            <div class="stat-item">
              <div class="stat-label">Lulus</div>
              <div class="stat-value stat-lulus">${stats.lulus}</div>
            </div>
          </div>
        </div>
      `;
    }
    statsHTML += '</div>';
    return statsHTML;
  }

  async function fetchAndRender() {
    try {
      const res = await fetch(`lihat_data_peserta.php?limit=${currentLimit}`);
      const data = await res.json();
      if (data.error) throw new Error(data.error);

      fullData = data;
      document.getElementById('content-placeholder').innerHTML = renderStats(data);
      renderTablePage();

      const cards = document.querySelectorAll('.stat-card');
      cards.forEach((card, i) => {
        setTimeout(() => card.classList.add('visible'), i * 150);
      });
    } catch (err) {
      console.error('Error:', err);
      document.getElementById('content-placeholder').innerHTML = `
        <div style="text-align:center; padding:40px; color:red;">
          Gagal memuat: ${err.message}
        </div>
      `;
    }
  }

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
        const avatar = document.getElementById('header-avatar');
        const username = document.getElementById('header-username');
        if (avatar && userData.logged_in_foto_profil_path) {
          avatar.src = userData.logged_in_foto_profil_path;
          avatar.onerror = () => avatar.src = 'images/profile.png';
        }
        if (username) {
          username.textContent = userData.logged_in_username || 'Akun';
        }
      }
    } catch (err) {
      console.warn('Gagal muat header/footer/user');
    }
    await fetchAndRender();
  }

  document.addEventListener('DOMContentLoaded', init);
</script>
</body>
</html>