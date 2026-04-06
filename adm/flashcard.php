<?php
session_start();
// Chú ý: đường dẫn đến DB tùy theo thực tế của dự án, giả định dùng chung của view
$db_path = '../view/db.php';
if (file_exists($db_path)) {
    require_once $db_path;
} elseif (file_exists('../db.php')) {
    require_once '../db.php';
}

$topics = [];
try {
    if (isset($pdo)) {
        // Biến xử lý xóa chủ đề
        if (isset($_GET['delete_id'])) {
            $del_id = (int)$_GET['delete_id'];
            $stmt = $pdo->prepare("DELETE FROM flashcard_topics WHERE id = ?");
            if ($stmt->execute([$del_id])) {
                $_SESSION['msg'] = "Đã xóa chủ đề thành công.";
            }
            header("Location: flashcard.php");
            exit;
        }

        $stmt = $pdo->query("SELECT * FROM flashcard_topics ORDER BY level ASC, created_at DESC");
        $topics = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    $error = "Lỗi truy vấn CSDL: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Quản lý Flashcard - JLPT AI ADMIN</title>
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
            min-height: 100vh;
        }
        .navbar { 
            background: var(--primary-gradient) !important;
            box-shadow: 0 2px 15px rgba(13, 148, 136, 0.15);
            border-bottom: 3px solid var(--accent-color);
            padding: 0.8rem 0;
        }
        .navbar-brand { 
            font-weight: 800; 
            letter-spacing: -0.5px;
            color: #ffffff !important; 
        }
        .nav-link { 
            font-weight: 500; 
            color: #e0f2fe !important; 
            padding: 0.5rem 1rem !important;
            transition: all 0.3s ease;
        }
        .nav-link:hover, .nav-link.active { 
            color: #ffffff !important; 
            background: rgba(255,255,255,0.1);
            border-radius: 8px;
        }
        .card { 
            border-radius: 16px; 
            border: none; 
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .card:hover { 
            transform: translateY(-2px); 
            box-shadow: 0 8px 16px rgba(0,0,0,.15);
        }
        .text-primary { color: var(--primary-color) !important; }
        .level-badge { font-weight: bold; width: 45px; display: inline-block; text-align: center; }
        .bg-n5 { background-color: #3b82f6; color: white; }
        .bg-n4 { background-color: #10b981; color: white; }
        .bg-n3 { background-color: #f59e0b; color: white; }
        .bg-n2 { background-color: #ef4444; color: white; }
        .bg-n1 { background-color: #8b5cf6; color: white; }
    </style>
</head>
<body>

<?php include 'headadm_nav.php'; ?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold"><i class="bi bi-card-text text-primary"></i> Quản lý Flashcard</h2>
            <p class="text-muted mb-0">Quản lý các chủ đề từ vựng theo từng cấp độ JLPT</p>
        </div>
        <a href="add_flashcard.php" class="btn btn-primary fw-bold" style="background: var(--primary-gradient); border: none; border-radius: 10px;">
            <i class="bi bi-plus-circle"></i> Thêm Chủ đề mới
        </a>
    </div>

    <?php if (isset($_SESSION['msg'])): ?>
        <div class="alert alert-success alert-dismissible fade show shadow-sm border-0" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> <?php echo $_SESSION['msg']; unset($_SESSION['msg']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger shadow-sm border-0"><i class="bi bi-exclamation-triangle-fill"></i> <?php echo $error; ?></div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">ID</th>
                            <th>Cấp độ</th>
                            <th>Tên Chủ đề</th>
                            <th>Ngày tạo</th>
                            <th class="text-end pe-4">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($topics)): ?>
                        <tr>
                            <td colspan="5" class="text-center py-5 text-muted">
                                <i class="bi bi-folder-x fs-1 text-secondary mb-3 d-block"></i>
                                Chưa có chủ đề Flashcard nào. <br>
                                <a href="add_flashcard.php" class="btn btn-sm btn-outline-primary mt-3">Thêm ngay</a>
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($topics as $topic): ?>
                            <tr>
                                <td class="ps-4 text-muted">#<?php echo $topic['id']; ?></td>
                                <td>
                                    <?php 
                                        $lvlClass = 'bg-secondary';
                                        if($topic['level'] == 'N5') $lvlClass = 'bg-n5';
                                        if($topic['level'] == 'N4') $lvlClass = 'bg-n4';
                                        if($topic['level'] == 'N3') $lvlClass = 'bg-n3';
                                        if($topic['level'] == 'N2') $lvlClass = 'bg-n2';
                                        if($topic['level'] == 'N1') $lvlClass = 'bg-n1';
                                    ?>
                                    <span class="badge level-badge <?php echo $lvlClass; ?> rounded-pill"><?php echo htmlspecialchars($topic['level']); ?></span>
                                </td>
                                <td class="fw-bold">
                                    <a href="flashcard_detail.php?topic_id=<?php echo $topic['id']; ?>" class="text-decoration-none text-dark">
                                        <?php echo htmlspecialchars($topic['name']); ?>
                                    </a>
                                </td>
                                <td><small class="text-muted"><?php echo date('d/m/Y H:i', strtotime($topic['created_at'])); ?></small></td>
                                <td class="text-end pe-4">
                                    <a href="flashcard_detail.php?topic_id=<?php echo $topic['id']; ?>" class="btn btn-sm btn-info text-white me-1" title="Xem & Thêm từ vựng mới">
                                        <i class="bi bi-eye"></i> Chi tiết
                                    </a>
                                    <a href="flashcard.php?delete_id=<?php echo $topic['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Bạn có chắc chắn muốn xóa Chủ đề này và TẤT CẢ từ vựng bên trong không?');" title="Xóa chủ đề">
                                        <i class="bi bi-trash"></i>
                                    </a>
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
