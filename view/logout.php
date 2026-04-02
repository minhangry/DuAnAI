<?php
// logout.php: Đăng xuất tài khoản JLPT
session_start();
session_unset();
session_destroy();
header('Location: login.php');
exit;
