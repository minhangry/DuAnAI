<?php
session_start();

$db_path = '../view/db.php';
if (file_exists($db_path)) {
    require_once $db_path;
} elseif (file_exists('../db.php')) {
    require_once '../db.php';
}

if (!isset($_GET['topic_id'])) {
    header('Location: flashcard.php');
    exit;
}

$topic_id = (int)$_GET['topic_id'];
$topic = null;
$words = [];

try {
    if (isset($pdo)) {
        // Xử lý XÓA từ vựng
        if (isset($_GET['delete_word'])) {
            $del_id = (int)$_GET['delete_word'];
            $stmt = $pdo->prepare("DELETE FROM flashcard_words WHERE id = ? AND topic_id = ?");
            if ($stmt->execute([$del_id, $topic_id])) {
                $_SESSION['msg'] = "Đã xóa từ vựng thành công.";
            }
            header("Location: flashcard_detail.php?topic_id=$topic_id");
            exit;
        }

        // Xử lý THÊM NHANH 1 từ vựng vao chủ đề
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_single'])) {
            $w = trim($_POST['word']);
            $r = trim($_POST['reading']);
            $m = trim($_POST['meaning']);
            $e = trim($_POST['example'] ?? '');

            if ($w && $m) {
                $stmt = $pdo->prepare("INSERT INTO flashcard_words (topic_id, word, reading, meaning, example) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$topic_id, $w, $r, $m, $e]);
                $_SESSION['msg'] = "Thêm từ mới '$w' thành công!";
            }
            header("Location: flashcard_detail.php?topic_id=$topic_id");
            exit;
        }

        // Lấy thông tin chủ đề
        $stmtTopic = $pdo->prepare("SELECT * FROM flashcard_topics WHERE id = ?");
        $stmtTopic->execute([$topic_id]);
        $topic = $stmtTopic->fetch(PDO::FETCH_ASSOC);

        if (!$topic) {
            die("Không tìm thấy chủ đề này.");
        }

        // Lấy danh sách từ vựng
        $stmtWords = $pdo->prepare("SELECT * FROM flashcard_words WHERE topic_id = ? ORDER BY id DESC");
        $stmtWords->execute([$topic_id]);
        $words = $stmtWords->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    $error = "Lỗi DB: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Chi tiết Chủ đề - JLPT AI ADMIN</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@400;700&family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #0d9488;
            --primary-gradient: linear-gradient(135deg, #0d9488 0%, #0284c7 100%);
            --accent-color: #f97316;
            --text-main: #0f172a;
            --bg-body: #f1f5f9;
        }
        body { 
            font-family: 'Inter', 'Noto Sans JP', sans-serif; 
            background-color: var(--bg-body);
            color: var(--text-main);
        }
        .navbar { 
            background: var(--primary-gradient) !important;
            border-bottom: 3px solid var(--accent-color);
        }
        .navbar-brand { font-weight: 800; color: #ffffff !important; }
        .nav-link { font-weight: 500; color: #e0f2fe !important; }
        .nav-link:hover, .nav-link.active { color: #ffffff !important; }
        .card { border-radius: 12px; border: none; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        .jap-text { font-family: 'Noto Sans JP', sans-serif; font-size: 1.2rem; }
    </style>
</head>
<body>

<?php include 'headadm_nav.php'; ?>

<div class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="flashcard.php" class="text-decoration-none">Quản lý Flashcard</a></li>
            <li class="breadcrumb-item active" aria-current="page">Chi tiết: <?php echo htmlspecialchars($topic['name'] ?? ''); ?></li>
        </ol>
    </nav>

    <?php if (isset($_SESSION['msg'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill"></i> <?php echo $_SESSION['msg']; unset($_SESSION['msg']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row mb-4">
        <div class="col-md-7">
            <div class="card p-4 h-100">
                <h4 class="fw-bold mb-3"><i class="bi bi-tag-fill text-primary"></i> <?php echo htmlspecialchars($topic['name'] ?? ''); ?></h4>
                <div class="d-flex align-items-center mb-2">
                    <span class="badge bg-primary fs-6 me-3">Level: <?php echo htmlspecialchars($topic['level'] ?? ''); ?></span>
                    <span class="text-muted"><i class="bi bi-layer-backward"></i> Tổng cộng: <?php echo count($words); ?> từ vựng</span>
                </div>
            </div>
        </div>
        <div class="col-md-5">
            <div class="card p-4 h-100 bg-primary bg-opacity-10 border border-primary border-opacity-25">
                <h5 class="fw-bold mb-3">Thêm nhanh 1 từ vựng</h5>
                <form action="flashcard_detail.php?topic_id=<?php echo $topic_id; ?>" method="POST">
                    <input type="hidden" name="add_single" value="1">
                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <input type="text" name="word" class="form-control form-control-sm" placeholder="Từ vựng (Kanji)" required>
                        </div>
                        <div class="col-6">
                            <input type="text" name="reading" class="form-control form-control-sm" placeholder="Cách đọc (Hiragana)" required>
                        </div>
                    </div>
                    <div class="mb-2">
                        <input type="text" name="meaning" class="form-control form-control-sm" placeholder="Ý nghĩa" required>
                    </div>
                    <div class="mb-2">
                        <input type="text" name="example" class="form-control form-control-sm" placeholder="Ví dụ (Không bắt buộc)">
                    </div>
                    <button type="submit" class="btn btn-sm btn-primary w-100"><i class="bi bi-plus"></i> Thêm từ này</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Bảng danh sách từ vựng -->
    <div class="card">
        <div class="card-header bg-white py-3">
            <h5 class="fw-bold mb-0">Danh sách từ vựng</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0">
                    <thead class="table-secondary">
                        <tr>
                            <th class="ps-4">#</th>
                            <th>Trạng thái/Kanji</th>
                            <th>Cách đọc</th>
                            <th>Ý nghĩa</th>
                            <th>Ví dụ</th>
                            <th class="text-end pe-4">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($words)): ?>
                            <tr><td colspan="6" class="text-center py-4 text-muted">Chủ đề này chưa có từ vựng nào.</td></tr>
                        <?php else: ?>
                            <?php foreach ($words as $index => $w): ?>
                            <tr>
                                <td class="ps-4 text-muted"><?php echo $index + 1; ?></td>
                                <td class="fw-bold jap-text text-primary fs-5"><?php echo htmlspecialchars($w['word']); ?></td>
                                <td class="jap-text"><?php echo htmlspecialchars($w['reading']); ?></td>
                                <td class="fw-semibold text-danger"><?php echo htmlspecialchars($w['meaning']); ?></td>
                                <td class="jap-text fst-italic text-muted small"><?php echo htmlspecialchars($w['example']); ?></td>
                                <td class="text-end pe-4">
                                    <a href="flashcard_detail.php?topic_id=<?php echo $topic_id; ?>&delete_word=<?php echo $w['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Xóa từ vựng này?');"><i class="bi bi-trash"></i></a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
