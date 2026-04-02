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
<?php include 'includes/header.php'; ?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <h2 class="mb-4 text-center fw-bold text-primary">Đăng ký thành viên</h2>
                    <?php if ($errors): ?>
                        <div class="alert alert-danger small">
                            <?php foreach ($errors as $e) echo '<div>' . htmlspecialchars($e) . '</div>'; ?>
                        </div>
                    <?php endif; ?>
                    <form method="POST" autocomplete="off">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Tên đăng nhập</label>
                            <input type="text" name="username" class="form-control" required value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Email</label>
                            <input type="email" name="email" class="form-control" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                        </div>
                        <div class="row mb-3">
                            <div class="col">
                                <label class="form-label fw-semibold">Mật khẩu</label>
                                <input type="password" name="password" class="form-control" required>
                            </div>
                            <div class="col">
                                <label class="form-label fw-semibold">Nhập lại</label>
                                <input type="password" name="confirm" class="form-control" required>
                            </div>
                        </div>
                        <div class="row mb-4">
                            <div class="col">
                                <label class="form-label fw-semibold small text-uppercase">Trình độ hiện tại</label>
                                <select name="current_level" class="form-select">
                                    <option value="Beginner">Beginner</option>
                                    <option value="N5">N5</option><option value="N4">N4</option><option value="N3">N3</option><option value="N2">N2</option>
                                </select>
                            </div>
                            <div class="col">
                                <label class="form-label fw-semibold small text-uppercase">Mục tiêu</label>
                                <select name="target_level" class="form-select text-primary fw-bold">
                                    <option value="N5">N5</option><option value="N4">N4</option><option value="N3">N3</option><option value="N2">N2</option>
                                </select>
                            </div>
                        </div>
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg">Tạo tài khoản</button>
                        </div>
                    </form>
                    <div class="mt-4 text-center">
                        Đã có tài khoản? <a href="login.php" class="text-decoration-none">Đăng nhập</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include 'includes/footer.php'; ?>
