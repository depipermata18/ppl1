<?php
session_start();

echo json_encode([
    "login" => isset($_SESSION['id_admin']) ? true : false
]);
