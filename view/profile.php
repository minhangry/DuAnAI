<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
require_once 'db.php';

$user_id = $_SESSION['user_id'];
$success = false;
$errors = [];

// Xử lý cập nhật thông tin
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_username = trim($_POST['username'] ?? '');
    $new_target_level = $_POST['target_level'] ?? '';

    if ($new_username === '') {
        $errors[] = "Tên hiển thị không được để trống.";
    }

    if (empty($errors)) {
        try {
            // Kiểm tra username đã tồn tại chưa (trừ chính mình)
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
            $stmt->execute([$new_username, $user_id]);
            if ($stmt->fetch()) {
                $errors[] = "Tên đăng nhập này đã được sử dụng.";
            } else {
                $stmt = $pdo->prepare("UPDATE users SET username = ?, target_level = ? WHERE id = ?");
                $stmt->execute([$new_username, $new_target_level, $user_id]);
                $_SESSION['username'] = $new_username; // Cập nhật lại session
                $success = true;
            }
        } catch (PDOException $e) {
            $errors[] = "Lỗi hệ thống: " . $e->getMessage();
        }
    }
}

// Lấy thông tin hiện tại
$stmt = $pdo->prepare("SELECT username, email, current_level, target_level FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
?>
<?php include 'includes/header.php'; ?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <h2 class="mb-4 fw-bold text-primary"><i class="bi bi-person-circle"></i> Hồ sơ cá nhân</h2>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success border-0 shadow-sm mb-4">
                            <i class="bi bi-check-circle-fill me-2"></i> Cập nhật thông tin thành công!
                        </div>
                    <?php endif; ?>

                    <?php if ($errors): ?>
                        <div class="alert alert-danger border-0 shadow-sm mb-4">
                            <?php foreach ($errors as $e) echo '<div><i class="bi bi-exclamation-triangle-fill me-2"></i>' . htmlspecialchars($e) . '</div>'; ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Email (Không thể thay đổi)</label>
                            <input type="email" class="form-control bg-light shadow-none" value="<?php echo htmlspecialchars($user['email']); ?>" readonly disabled style="cursor: not-allowed;">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Tên hiển thị</label>
                            <input type="text" name="username" class="form-control shadow-sm" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold">Mục tiêu JLPT</label>
                            <select name="target_level" class="form-select text-primary fw-bold shadow-sm">
                                <?php foreach(['N5', 'N4', 'N3', 'N2'] as $lv): ?>
                                    <option value="<?php echo $lv; ?>" <?php echo ($user['target_level'] === $lv) ? 'selected' : ''; ?>><?php echo $lv; ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text mt-2 small text-muted">Hệ thống AI sẽ dựa trên mục tiêu này để gợi ý đề thi và bài học phù hợp.</div>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg py-3">
                                <i class="bi bi-save me-2"></i> Lưu thay đổi
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>