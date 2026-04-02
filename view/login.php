<?php
// login.php: Đăng nhập tài khoản JLPT
session_start();
$dbHost = '127.0.0.1';
$dbName = 'jlpt_ai_learning';
$dbUser = 'root';
$dbPass = '';

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    if ($username === '' || $password === '') {
        $errors[] = 'Vui lòng nhập đầy đủ thông tin.';
    } else {
        try {
            $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4", $dbUser, $dbPass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                header('Location: quiz.php');
                exit;
            } else {
                $errors[] = 'Tên đăng nhập/email hoặc mật khẩu không đúng.';
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
    <title>Đăng nhập JLPT</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container py-4" style="max-width:400px;">
    <h2 class="mb-4 text-center">Đăng nhập</h2>
    <?php if ($errors): ?>
        <div class="alert alert-danger">
            <?php foreach ($errors as $e) echo '<div>' . htmlspecialchars($e) . '</div>'; ?>
        </div>
    <?php endif; ?>
    <form method="POST" autocomplete="off">
        <div class="mb-3">
            <label class="form-label">Tên đăng nhập hoặc Email</label>
            <input type="text" name="username" class="form-control" required value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
        </div>
        <div class="mb-3">
            <label class="form-label">Mật khẩu</label>
            <input type="password" name="password" class="form-control" required>
        </div>
        <div class="d-grid">
            <button type="submit" class="btn btn-primary btn-lg">Đăng nhập</button>
        </div>
    </form>
    <div class="mt-3 text-center">
        Chưa có tài khoản? <a href="dangky.php">Đăng ký</a>
    </div>
</div>
</body>
</html>
