<?php
// File: dashboard_peserta.php

// Anda bisa menambahkan logika pengecekan sesi (session) di sini
// untuk memastikan pengguna sudah login sebelum menampilkan dashboard.

// Contoh sederhana untuk mengarahkan ke halaman logout
// (Anda perlu membuat file 'logout.php' terpisah)

if (isset($_POST['logout'])) {
    // Di sini adalah tempat Anda akan menghancurkan sesi
    session_start();
    session_destroy();
    
    // Alihkan pengguna ke halaman login atau halaman utama
    header("Location: login.php"); // Ganti dengan halaman login Anda
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Peserta</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            background-color: #f4f4f9;
        }
        .container {
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        .logout-btn {
            background-color: #dc3545;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            margin-top: 15px;
            transition: background-color 0.3s;
        }
        .logout-btn:hover {
            background-color: #c82333;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Selamat Datang di Dashboard Peserta 👋</h2>
        <p>Hanya ada satu aksi yang tersedia.</p>
        
        <form method="POST" action="">
            <button type="submit" name="logout" class="logout-btn">
                Keluar (Logout) 🚪
            </button>
        </form>
    </div>
</body>
</html>