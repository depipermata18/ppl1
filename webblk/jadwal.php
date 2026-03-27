<?php
session_start();
include 'config/database.php';

if (!isset($_SESSION['nik'])) {
    header("Location: login.php");
    exit;
}

$nik = $_SESSION['nik'];

// Ambil data peserta
$pes = $conn->prepare("SELECT nama_peserta FROM peserta WHERE nik=?");
$pes->bind_param("s", $nik);
$pes->execute();
$d_pes = $pes->get_result()->fetch_assoc();
$nama = $d_pes['nama_peserta'];

// Ambil semua jadwal masa depan, urutkan berdasarkan waktu_mulai
$q = $conn->prepare("
    SELECT * FROM jadwal 
    WHERE waktu_mulai > NOW() 
    ORDER BY waktu_mulai ASC
");
$q->execute();
$jadwal = $q->get_result();

// Kelompokkan jadwal berdasarkan tanggal
$events_by_date = [];
while ($row = $jadwal->fetch_assoc()) {
    $date_key = date('Y-m-d', strtotime($row['waktu_mulai']));
    if (!isset($events_by_date[$date_key])) {
        $events_by_date[$date_key] = [];
    }
    $events_by_date[$date_key][] = $row;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Jadwal Kegiatan | e-BLK</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
/* === LOADING SCREEN === */
.page-loader {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(255, 255, 255, 0.94);
    backdrop-filter: blur(4px);
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    z-index: 9999;
    transition: opacity 0.3s, visibility 0.3s;
}
.page-loader .logo {
    width: 90px;
    margin-bottom: 12px;
    animation: pulseLogo 1s infinite alternate;
}
.page-loader .text {
    font-size: 16px;
    color: #4F80FF;
    font-weight: 600;
    letter-spacing: 0.5px;
}
@keyframes pulseLogo {
    from { transform: scale(1); opacity: 0.9; }
    to { transform: scale(1.05); opacity: 1; }
}

body {
    font-family: 'Poppins', sans-serif;
    margin: 0;
    background: linear-gradient(135deg, #ffffff, #ffffff);
    color: #333333;
    line-height: 1.6;
}

.header {
    position: fixed;
    top: 0;
    left: 0;
    height: 70px;
    width: 100%;
    padding: 0 40px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    z-index: 1000;
    background: #ffffff;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    box-sizing: border-box;
}

.logo img {
    height: 50px;
    object-fit: contain;
    max-width: 100%;
}

.nav {
    display: flex;
    align-items: center;
    gap: 30px;
    margin-right: 20px;
}

.nav a {
    color: #333333;
    text-decoration: none;
    font-size: 16px;
    font-weight: 500;
    transition: color 0.3s ease;
}

.nav a:hover,
.nav a.active {
    color: #4F80FF;
}

.menu-toggle {
    display: none !important;
}

.main-content {
    margin-top: 90px;
    padding: 20px;
    max-width: 1000px;
    margin-left: auto;
    margin-right: auto;
}

/* Kalender Bulanan */
.calendar-container {
    background: white;
    border-radius: 15px;
    padding: 20px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}

.calendar-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.calendar-title {
    font-size: 20px;
    font-weight: 600;
    color: #333;
}

.calendar-nav button {
    background: none;
    border: 1px solid #ddd;
    padding: 5px 10px;
    border-radius: 5px;
    cursor: pointer;
    font-size: 14px;
}

.calendar-nav button:hover {
    background: #f0f0f0;
}

.calendar-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 5px;
    margin-bottom: 15px;
}

.calendar-day-label {
    text-align: center;
    font-weight: 600;
    padding: 8px;
    background: #f8f9fa;
    border-radius: 5px;
    font-size: 14px;
}

.calendar-day {
    text-align: center;
    padding: 10px;
    border-radius: 8px;
    cursor: pointer;
    transition: background 0.2s;
    font-size: 14px;
    position: relative;
}

.calendar-day:hover {
    background: #e9f0ff;
}

.calendar-day.today {
    background: #4F80FF;
    color: white;
}

.calendar-day.has-event {
    background: #e0f7fa;
    border: 2px solid #00bcd4;
}

/* Daftar Jadwal Per Hari */
.event-list-container {
    background: white;
    border-radius: 15px;
    padding: 20px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.event-date {
    font-size: 18px;
    font-weight: 600;
    color: #000;
    margin: 20px 0 10px 0;
    padding: 10px;
    background: #f0f7ff;
    border-radius: 8px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.event-item {
    background: #4F80FF;
    border-radius: 10px;
    padding: 15px;
    margin-bottom: 10px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.05);
    display: flex;
    gap: 15px;
    align-items: flex-start;
    cursor: pointer;
    transition: transform 0.2s, box-shadow 0.2s;
}

.event-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 10px rgba(0,0,0,0.1);
}

.event-time {
    min-width: 80px;
    text-align: center;
    padding: 5px 10px;
    background: #f0f5ff;
    border-radius: 5px;
    font-size: 14px;
    font-weight: 500;
}

.event-details {
    flex: 1;
}

.event-title {
    font-size: 16px;
    font-weight: 600;
    color: white;
    margin: 0 0 5px 0;
}

.event-location {
    font-size: 14px;
    color: #cce5ff;
    margin: 0;
}

.event-desc {
    font-size: 13px;
    color: #cce5ff;
    margin: 5px 0 0 0;
}

/* Modal Styling */
.modal {
    display: none;
    position: fixed;
    z-index: 1001;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
    padding: 20px;
    overflow-y: auto;
}

.modal-content {
    background: white;
    margin: 10% auto;
    padding: 20px;
    border-radius: 15px;
    max-width: 600px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.2);
    position: relative;
}

.close {
    color: #aaa;
    float: right;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
    transition: color 0.3s;
}

.close:hover {
    color: #000;
}

.modal-content h3 {
    margin-top: 0;
    color: #4F80FF;
    border-bottom: 2px solid #4F80FF;
    padding-bottom: 10px;
}

.modal-field {
    margin: 15px 0;
    padding: 12px;
    background: #f8f9fa;
    border-radius: 8px;
    border-left: 4px solid #4F80FF;
}

.modal-label {
    font-weight: 600;
    color: #333;
    display: block;
    margin-bottom: 5px;
}

.modal-value {
    font-size: 16px;
    color: #000;
}

/* Responsive */
@media (max-width: 768px) {
    .header {
        padding: 0 15px;
        height: 60px;
    }
    .logo img { height: 40px; }
    
    .nav {
        display: none;
        position: absolute;
        top: 60px;
        left: 0;
        width: 100%;
        background: white;
        flex-direction: column;
        padding: 12px 0;
        box-shadow: 0 6px 20px rgba(0,0,0,0.12);
        border-radius: 0 0 16px 16px;
        z-index: 999;
        gap: 0;
    }
    .nav.active {
        display: flex !important;
    }
    .nav a {
        padding: 12px 20px;
        font-size: 15px;
        border-bottom: 1px solid #eee;
        margin: 0;
        transition: all 0.2s ease;
    }
    .nav a:last-child { border-bottom: none; }
    .nav a:hover {
        background: #f0f7ff;
        transform: translateX(4px);
    }

    .menu-toggle {
        display: flex !important;
        background: none;
        border: none;
        font-size: 26px;
        color: #4F80FF;
        cursor: pointer;
        margin-left: auto;
        width: 36px;
        height: 36px;
        border-radius: 50%;
        align-items: center;
        justify-content: center;
        transition: background 0.3s;
    }
    .menu-toggle:hover {
        background: #f0f7ff;
    }

    .main-content { margin-top: 80px; }
    .calendar-container { padding: 15px; }
    .calendar-title { font-size: 18px; }
    .calendar-day { padding: 8px; font-size: 12px; }
    .event-date { font-size: 16px; }
    .event-item { padding: 12px; }
    .event-time { min-width: 60px; font-size: 12px; }
    .event-title { font-size: 14px; }
    .event-location, .event-desc { font-size: 12px; }

    /* Modal HP */
    .modal-content {
        margin: 5% auto;
        width: 95%;
    }
}

@media (max-width: 480px) {
    .header { height: 55px; }
    .logo img { height: 35px; }
    .calendar-title { font-size: 16px; }
    .calendar-day { font-size: 10px; padding: 6px; }
    .event-date { font-size: 14px; }
    .event-item { padding: 10px; }
    .event-time { min-width: 50px; font-size: 10px; }
    .event-title { font-size: 12px; }
}
</style>
</head>

<body>

<div class="page-loader" id="pageLoader">
    <img src="image/logo.png" alt="Logo" class="logo">
    <div class="text">Memuat...</div>
</div>

<div class="header">
    <div class="logo">
        <img src="image/logo.png" alt="Logo BLK">
    </div>
    
    <script>
    if (window.innerWidth <= 768) {
        document.write('<button class="menu-toggle" onclick="toggleMenu()">☰</button>');
    }
    </script>
    
    <div class="nav">
        <a href="dashboard_peserta.php">HOME</a>
        <a href="jadwal.php" class="active">JADWAL</a>
        <a href="riwayat.php">RIWAYAT</a>
        <a href="profil_peserta.php">PROFIL</a>
    </div>
</div>

<div class="main-content">
    <!-- Kalender Bulanan -->
    <div class="calendar-container">
        <div class="calendar-header">
            <h3 class="calendar-title"><?= date('F Y') ?></h3>
            <div class="calendar-nav">
                <button>❮</button>
                <button>❯</button>
            </div>
        </div>
        
        <div class="calendar-grid">
            <?php
            $days = ['S', 'M', 'T', 'W', 'T', 'F', 'S'];
            foreach ($days as $day) {
                echo "<div class='calendar-day-label'>$day</div>";
            }
            
            // Generate calendar days for current month
            $current_month = date('m');
            $current_year = date('Y');
            $first_day = mktime(0, 0, 0, $current_month, 1, $current_year);
            $first_day_weekday = date('w', $first_day);
            $days_in_month = date('t', $first_day);
            
            // Empty cells before first day
            for ($i = 0; $i < $first_day_weekday; $i++) {
                echo "<div class='calendar-day'></div>";
            }
            
            // Days of the month
            for ($day = 1; $day <= $days_in_month; $day++) {
                $date_str = "$current_year-$current_month-" . str_pad($day, 2, '0', STR_PAD_LEFT);
                $is_today = $date_str == date('Y-m-d');
                $has_event = isset($events_by_date[$date_str]);
                
                $class = "calendar-day";
                if ($is_today) $class .= " today";
                if ($has_event) $class .= " has-event";
                
                echo "<div class='$class'>$day</div>";
            }
            ?>
        </div>
    </div>

    <!-- Daftar Jadwal Per Hari -->
    <div class="event-list-container">
        <h2 style="margin-top: 0; color: #333;">Jadwal Mendatang</h2>
        
        <?php if (count($events_by_date) > 0): ?>
            <?php foreach ($events_by_date as $date => $events): ?>
                <div class="event-date">
                    <span>📅 <?= date('l, d F Y', strtotime($date)) ?></span>
                </div>
                <?php foreach ($events as $event): ?>
                    <div class="event-item" onclick='showEventDetails(<?= json_encode($event) ?>)'>
                        <div class="event-time">
                            <?= date('H:i', strtotime($event['waktu_mulai'])) ?><br>
                            <?= date('H:i', strtotime($event['waktu_selesai'])) ?>
                        </div>
                        <div class="event-details">
                            <div class="event-title"><?= htmlspecialchars($event['nama_jadwal']) ?></div>
                            <div class="event-location">📍 <?= htmlspecialchars($event['lokasi']) ?></div>
                            <?php if (!empty($event['keterangan'])): ?>
                                <div class="event-desc">📝 <?= htmlspecialchars($event['keterangan']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endforeach; ?>
        <?php else: ?>
            <p style="text-align: center; padding: 20px; color: #666;">Tidak ada jadwal mendatang.</p>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Popup -->
<div id="eventModal" class="modal" style="display: none;">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>
        <h3 id="modalTitle">Detail Jadwal</h3>
        <div id="modalBody"></div>
    </div>
</div>

<script>
function toggleMenu() {
    document.querySelector('.nav').classList.toggle('active');
}

function showEventDetails(eventData) {
    const modal = document.getElementById('eventModal');
    const title = document.getElementById('modalTitle');
    const body = document.getElementById('modalBody');

    title.textContent = eventData.nama_jadwal;
    body.innerHTML = '';

    for (let key in eventData) {
        if (key !== 'id_jadwal') {
            const fieldDiv = document.createElement('div');
            fieldDiv.className = 'modal-field';

            const label = document.createElement('div');
            label.className = 'modal-label';
            // Format label: id_admin → Id Admin
            let labelText = key.replace(/_/g, ' ');
            labelText = labelText.replace(/\b\w/g, char => char.toUpperCase());
            label.textContent = labelText;

            const value = document.createElement('div');
            value.className = 'modal-value';

            if (key === 'waktu_mulai' || key === 'waktu_selesai') {
                const date = new Date(eventData[key]);
                value.textContent = date.toLocaleString('id-ID', {
                    day: '2-digit',
                    month: 'long',
                    year: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit',
                    hour12: false
                });
            } else {
                value.textContent = eventData[key] || '-';
            }

            fieldDiv.appendChild(label);
            fieldDiv.appendChild(value);
            body.appendChild(fieldDiv);
        }
    }

    modal.style.display = 'block';
}

function closeModal() {
    document.getElementById('eventModal').style.display = 'none';
}

// Tutup modal jika klik di luar
window.onclick = function(event) {
    const modal = document.getElementById('eventModal');
    if (event.target === modal) {
        closeModal();
    }
}

// Loading screen
document.addEventListener("DOMContentLoaded", function () {
    const loader = document.getElementById('pageLoader');
    setTimeout(() => {
        loader.style.opacity = '0';
        setTimeout(() => {
            if (loader.parentNode) loader.remove();
        }, 300);
    }, 500);
});
</script>

</body>
</html>