<!-- Sidebar / Navbar -->
<div class="sidebar">
  <div>
    <h2>Admin e-BLK</h2>
    <div class="nav">
      <a href="dashboard_admin.php" class="<?= basename($_SERVER['PHP_SELF']) == 'dashboard_admin.php' ? 'active' : '' ?>">🏠 Home</a>
      <a href="kejuruan.php" class="<?= basename($_SERVER['PHP_SELF']) == 'kejuruan.php' ? 'active' : '' ?>">🛠 Kejuruan</a>
      <a href="kelola_peserta.php" class="<?= basename($_SERVER['PHP_SELF']) == 'kelola_peserta.php' ? 'active' : '' ?>">👥 Kelola Peserta</a>
      <a href="jadwal.php" class="<?= basename($_SERVER['PHP_SELF']) == 'jadwal.php' ? 'active' : '' ?>">📅 Jadwal</a>
    </div>
  </div>
  <div class="logout">
    <a href="logout.php">Keluar</a>
  </div>
</div>
