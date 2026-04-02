<?php
// dangky.php: Đăng ký tài khoản JLPT
session_start();
$dbHost = '127.0.0.1';
$dbName = 'jlpt_ai_learning';
$dbUser = 'root';
$dbPass = '';

$errors = [];
$success = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm'] ?? '';
    $current_level = $_POST['current_level'] ?? 'Beginner';
    $target_level = $_POST['target_level'] ?? 'N5';

    $email = trim($email); // Loại bỏ khoảng trắng thừa
    if ($username === '' || $email === '' || $password === '' || $confirm === '') {
        $errors[] = 'Vui lòng nhập đầy đủ thông tin.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email không hợp lệ. Định dạng đúng: example@gmail.com';
    } elseif ($password !== $confirm) {
        $errors[] = 'Mật khẩu nhập lại không khớp.';
    } else {
        try {
            $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4", $dbUser, $dbPass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
            // Kiểm tra username/email trùng
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            if ($stmt->fetch()) {
                $errors[] = 'Tên đăng nhập hoặc email đã tồn tại.';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (username, password, email, current_level, target_level, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
                $stmt->execute([$username, $hash, $email, $current_level, $target_level]);
                $success = true;
                header('Location: login.php?registered=1');
                exit;
            }
        } catch (PDOException $e) {
            $errors[] = 'Lỗi kết nối CSDL: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đăng ký tài khoản JLPT</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container py-4" style="max-width:500px;">
    <h2 class="mb-4 text-center">Đăng ký tài khoản</h2>
    <?php if ($success): ?>
        <div class="alert alert-success">Đăng ký thành công! <a href="login.php">Đăng nhập ngay</a></div>
    <?php endif; ?>
    <?php if ($errors): ?>
        <div class="alert alert-danger">
            <?php foreach ($errors as $e) echo '<div>' . htmlspecialchars($e) . '</div>'; ?>
        </div>
    <?php endif; ?>
    <form method="POST" autocomplete="off">
        <div class="mb-3">
            <label class="form-label">Tên đăng nhập</label>
            <input type="text" name="username" class="form-control" required value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
        </div>
        <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
        </div>
        <div class="mb-3">
            <label class="form-label">Mật khẩu</label>
            <input type="password" name="password" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Nhập lại mật khẩu</label>
            <input type="password" name="confirm" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Trình độ hiện tại</label>
            <select name="current_level" class="form-select">
                <option value="Beginner">Beginner</option>
                <option value="N5">N5</option>
                <option value="N4">N4</option>
                <option value="N3">N3</option>
                <option value="N2">N2</option>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">Trình độ mục tiêu</label>
            <select name="target_level" class="form-select">
                <option value="N5">N5</option>
                <option value="N4">N4</option>
                <option value="N3">N3</option>
                <option value="N2">N2</option>
            </select>
        </div>
        <div class="d-grid">
            <button type="submit" class="btn btn-primary btn-lg">Đăng ký</button>
        </div>
    </form>
    <div class="mt-3 text-center">
        Đã có tài khoản? <a href="login.php">Đăng nhập</a>
    </div>
</div>
</body>
</html>
