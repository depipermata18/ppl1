<?php
$host = "srv1631.hstgr.io"; 
$user = "u137138991_blk2"; 
$pass = "BlK2024@Admin!!";
$db   = "u137138991_blk2";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Koneksi database gagal: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");
return $conn;
?>
