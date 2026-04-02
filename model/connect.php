<?php
// Kết nối MySQL sử dụng mysqli (XAMPP mặc định user là 'root', không mật khẩu)
$host = 'localhost';
$user = 'root';
$password = '';
$dbname = 'jlpt_ai_learning';

$conn = new mysqli($host, $user, $password, $dbname);

if ($conn->connect_error) {
    die('Kết nối thất bại: ' . $conn->connect_error);
}

// Các đoạn mã khác của bạn ở đây

?>