<!-- includes/header.php -->
<div id="page-transition" class="page-transition"></div>

<header id="mainHeader" class="header-visible">
  <div class="brand">
    <img src="images/logo.png" alt="Logo BLK" class="logo">
    <span class="title">Dashboard Admin</span>
  </div>

  <nav class="main-nav">
    <a href="dashboard_admin.php" data-page="dashboard">Home</a>
    <a href="kejuruan.php" data-page="kejuruan">Kejuruan</a>

    <!-- Dropdown: Kelola Data Peserta -->
    <div class="dropdown" data-page="peserta">
      <a href="#" class="dropdown-toggle">Kelola Data Peserta ▼</a>
      <div class="dropdown-menu">
        <a href="lihat_data_peserta.php">Lihat Data Peserta</a>
        <a href="kelola_data_peserta.php">Manajemen Data Peserta</a>
      </div>
    </div>

    <a href="kelola_jadwal.php" data-page="jadwal">Jadwal</a>
  </nav>

  <!-- Profil Admin -->
  <div class="profile-section">
    <div class="profile-dropdown">
      <a href="#" class="profile-trigger">
        <img src="images/profile.png" alt="Profil" class="avatar">
        <span class="username">Admin</span>
      </a>
      <div class="profile-menu">
        <a href="edit_profil.php">Edit Profil</a>
        <a href="logout.php" class="logout">Logout</a>
      </div>
    </div>
  </div>
</header>

<style>
  :root {
    --primary: #3565A5;
    --accent: #4a7bbd;
    --text: #1f2937;
    --muted: #6b7280;
    --card: #ffffff;
    --bg: #f1f5f9;
    --shadow: 0 2px 8px rgba(53, 101, 165, 0.15);
  }

  /* Page Transition Overlay */
  .page-transition {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: var(--card);
    z-index: 9999;
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.3s ease-in-out;
  }

  .page-transition.active {
    opacity: 1;
    pointer-events: all;
  }

  /* Header Styles */
  header {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    padding: 16px 32px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    z-index: 1000;
    background: var(--card);
    box-shadow: none;
    transform: translateY(0);
    opacity: 1;
    transition: 
      transform 0.3s ease-out,
      opacity 0.3s ease-out,
      box-shadow 0.3s ease-out;
  }

  header.scrolled {
    box-shadow: var(--shadow);
  }

  header.hidden {
    transform: translateY(-100%);
    opacity: 0;
    box-shadow: none;
  }

  /* Highlight menu aktif */
  .main-nav a.active,
  .dropdown.active > .dropdown-toggle {
    color: var(--primary) !important;
    background: rgba(53, 101, 165, 0.1);
    font-weight: 600;
    border-radius: 6px;
  }

  .brand {
    display: flex;
    align-items: center;
    gap: 12px;
  }

  .logo {
    height: 40px;
    width: auto;
    border-radius: 6px;
    object-fit: cover;
  }

  .title {
    font-size: 20px;
    font-weight: 600;
    color: var(--text);
  }

  .main-nav {
    display: flex;
    align-items: center;
    gap: 20px;
  }

  .main-nav a,
  .dropdown-toggle {
    text-decoration: none;
    font-weight: 500;
    font-size: 15px;
    color: var(--text);
    padding: 6px 10px;
    border-radius: 6px;
    transition: color 0.2s;
  }

  .main-nav a:hover,
  .dropdown-toggle:hover {
    color: var(--accent);
  }

  .dropdown {
    position: relative;
    display: inline-block;
  }

  .dropdown-menu {
    display: none;
    position: absolute;
    background: white;
    min-width: 200px;
    box-shadow: var(--shadow);
    border-radius: 8px;
    top: 100%;
    left: 0;
    padding: 8px 0;
    z-index: 1001;
  }

  .dropdown:hover .dropdown-menu,
  .dropdown.active:hover .dropdown-menu {
    display: block;
  }

  .dropdown-menu a {
    display: block;
    padding: 10px 20px;
    color: var(--text);
    text-decoration: none;
    font-size: 14px;
  }

  .dropdown-menu a:hover {
    background: #f3f4f6;
    color: var(--primary);
  }

  .profile-section {
    display: flex;
    align-items: center;
    gap: 12px;
  }

  .profile-trigger {
    display: flex;
    align-items: center;
    gap: 8px;
    color: var(--text);
    text-decoration: none;
    padding: 6px 10px;
    border-radius: 20px;
  }

  .profile-trigger:hover {
    color: var(--accent);
  }

  .avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid white;
    box-shadow: 0 0 0 2px var(--primary);
  }

  .profile-dropdown {
    position: relative;
    display: inline-block;
  }

  .profile-menu {
    display: none;
    position: absolute;
    background: white;
    min-width: 180px;
    box-shadow: var(--shadow);
    border-radius: 8px;
    top: 100%;
    right: 0;
    padding: 8px 0;
    z-index: 1001;
  }

  .profile-dropdown:hover .profile-menu {
    display: block;
  }

  .profile-menu a {
    display: block;
    padding: 10px 20px;
    color: var(--text);
    text-decoration: none;
    font-size: 14px;
  }

  .profile-menu a:hover {
    background: #f3f4f6;
    color: var(--primary);
  }

  .logout {
    color: #ef4444 !important;
  }

  .logout:hover {
    color: #dc2626 !important;
    background: #fee2e2;
  }

  @media (min-width: 769px) {
    .dropdown:hover .dropdown-menu {
      display: block;
    }
    .profile-dropdown:hover .profile-menu {
      display: block;
    }
  }

  @media (max-width: 768px) {
    header {
      padding: 12px 20px;
    }
    .main-nav { gap: 16px; }
    .profile-section { gap: 10px; }
    .title { font-size: 18px; }
  }
</style>

<script>
  document.addEventListener('DOMContentLoaded', () => {
    const header = document.getElementById('mainHeader');
    const transition = document.getElementById('page-transition');

    if (!header) return;

    // === FUNGSI: SET JUDUL & MENU AKTIF ===
    function setActivePage() {
      const path = window.location.pathname.split('/').pop();
      let pageTitle = "Dashboard Admin";
      let activePage = "dashboard";

      if (path === 'kejuruan.php') {
        pageTitle = "Kejuruan";
        activePage = "kejuruan";
      } else if (path === 'lihat_data_peserta.php' || path === 'kelola_data_peserta.php') {
        pageTitle = "Kelola Data Peserta";
        activePage = "peserta";
      } else if (path === 'kelola_jadwal.php') {
        pageTitle = "Kelola Jadwal";
        activePage = "jadwal";
      } else if (path === 'dashboard_admin.php' || path === '' || path === 'index.php') {
        pageTitle = "Dashboard Admin";
        activePage = "dashboard";
      }

      // Update judul
      const titleEl = document.querySelector('.title');
      if (titleEl) {
        titleEl.textContent = pageTitle;
      }

      // Reset semua active
      document.querySelectorAll('.main-nav a, .dropdown').forEach(el => {
        el.classList.remove('active');
      });

      // Set active
      const activeLink = document.querySelector(`.main-nav a[data-page="${activePage}"]`);
      const activeDropdown = document.querySelector(`.dropdown[data-page="${activePage}"]`);

      if (activeLink) activeLink.classList.add('active');
      if (activeDropdown) activeDropdown.classList.add('active');
    }

    // Jalankan saat load
    setActivePage();

    // === SCROLL BEHAVIOR: HIDE/SHOW HEADER ===
    let lastScrollTop = 0;
    const hideThreshold = 50;

    window.addEventListener('scroll', () => {
      const currentScroll = window.pageYOffset || document.documentElement.scrollTop;

      // Shadow saat scroll sedikit
      if (currentScroll > 10) {
        header.classList.add('scrolled');
      } else {
        header.classList.remove('scrolled');
      }

      // Hide on scroll down, show on scroll up
      if (currentScroll > hideThreshold && currentScroll > lastScrollTop) {
        header.classList.remove('header-visible');
        header.classList.add('hidden');
      } else if (currentScroll < lastScrollTop || currentScroll <= hideThreshold) {
        header.classList.remove('hidden');
        header.classList.add('header-visible');
      }

      lastScrollTop = currentScroll <= 0 ? 0 : currentScroll;
    });

    // === PAGE TRANSITION FADE ===
    if (transition) {
      setTimeout(() => {
        transition.classList.add('active');
        setTimeout(() => {
          transition.classList.remove('active');
        }, 50);
      }, 10);
    }

    // Intercept klik link internal
    document.querySelectorAll('a[href]').forEach(link => {
      const url = link.getAttribute('href');
      if (
        url &&
        !url.startsWith('#') &&
        !url.startsWith('http') &&
        !url.includes('logout.php')
      ) {
        link.addEventListener('click', (e) => {
          e.preventDefault();
          if (transition) {
            transition.classList.add('active');
            setTimeout(() => {
              window.location.href = url;
            }, 300);
          } else {
            window.location.href = url;
          }
        });
      }
    });
  });
</script>