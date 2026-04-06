<?php
session_start();
$db_path = '../view/db.php';
if (file_exists($db_path)) {
    require_once $db_path;
} elseif (file_exists('../db.php')) {
    require_once '../db.php';
}

$grammars = [];
try {
    if (isset($pdo)) {
        // Xử lý Xóa ngữ pháp
        if (isset($_GET['delete_id'])) {
            $del_id = (int)$_GET['delete_id'];
            $stmt = $pdo->prepare("DELETE FROM grammar_lessons WHERE id = ?");
            if ($stmt->execute([$del_id])) {
                $_SESSION['msg'] = "Đã xóa ngữ pháp thành công.";
            }
            header("Location: nguphap.php");
            exit;
        }

        // Lọc theo level (nếu có click tab)
        $filter_lvl = $_GET['level'] ?? null;
        if ($filter_lvl && in_array($filter_lvl, ['N5', 'N4', 'N3', 'N2', 'N1'])) {
            $stmt = $pdo->prepare("SELECT * FROM grammar_lessons WHERE level = ? ORDER BY created_at DESC");
            $stmt->execute([$filter_lvl]);
        } else {
            $stmt = $pdo->query("SELECT * FROM grammar_lessons ORDER BY level ASC, created_at DESC");
        }
        $grammars = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
    <title>Quản lý Ngữ pháp - JLPT AI ADMIN</title>
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
        }
        .navbar-brand { font-weight: 800; color: #ffffff !important; }
        .nav-link { font-weight: 500; color: #e0f2fe !important; }
        .nav-link:hover, .nav-link.active { color: #ffffff !important; }
        
        .level-badge { font-weight: bold; width: 45px; display: inline-block; text-align: center; }
        .bg-n5 { background-color: #3b82f6; color: white; }
        .bg-n4 { background-color: #10b981; color: white; }
        .bg-n3 { background-color: #f59e0b; color: white; }
        .bg-n2 { background-color: #ef4444; color: white; }
        .bg-n1 { background-color: #8b5cf6; color: white; }
        .jap-text { font-family: 'Noto Sans JP', sans-serif; }
    </style>
</head>
<body>

<?php include 'headadm_nav.php'; ?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold"><i class="bi bi-spellcheck text-primary"></i> Quản lý Ngữ pháp</h2>
            <p class="text-muted mb-0">Thêm, sửa, xóa cấu trúc ngữ pháp và câu ví dụ</p>
        </div>
        <a href="add_nguphap.php" class="btn btn-primary fw-bold px-4" style="background: var(--primary-gradient); border: none; border-radius: 10px;">
            <i class="bi bi-plus-lg"></i> Thêm Ngữ pháp
        </a>
    </div>

    <!-- Filter Tabs -->
    <ul class="nav nav-pills mb-4 gap-2">
        <li class="nav-item">
            <a class="nav-link <?php echo !$filter_lvl ? 'active' : 'bg-white text-dark shadow-sm'; ?>" href="nguphap.php">Tất cả</a>
        </li>
        <?php foreach(['N5','N4','N3','N2','N1'] as $l): ?>
        <li class="nav-item">
            <a class="nav-link <?php echo $filter_lvl == $l ? 'active' : 'bg-white text-dark shadow-sm'; ?>" href="nguphap.php?level=<?php echo $l; ?>"><?php echo $l; ?></a>
        </li>
        <?php endforeach; ?>
    </ul>

    <?php if (isset($_SESSION['msg'])): ?>
        <div class="alert alert-success alert-dismissible fade show shadow-sm border-0" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> <?php echo $_SESSION['msg']; unset($_SESSION['msg']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if (isset($error)): ?>
        <div class="alert alert-danger shadow-sm border-0"><i class="bi bi-exclamation-triangle-fill"></i> <?php echo $error; ?></div>
    <?php endif; ?>

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">#</th>
                            <th>Cấp độ</th>
                            <th>Cấu trúc ngữ pháp</th>
                            <th>Ý nghĩa</th>
                            <th>Số ví dụ</th>
                            <th class="text-end pe-4">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($grammars)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">Không có dữ liệu cấu trúc ngữ pháp nào.</td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($grammars as $index => $g): ?>
                            <?php
                                // Chuyển JSON thành mảng để đếm số câu ví dụ
                                $examples = json_decode($g['examples'], true);
                                $ex_count = is_array($examples) ? count($examples) : 0;
                            ?>
                            <tr>
                                <td class="ps-4 text-muted"><?php echo $index + 1; ?></td>
                                <td>
                                    <?php 
                                        $lvlClass = 'bg-secondary';
                                        if($g['level'] == 'N5') $lvlClass = 'bg-n5';
                                        if($g['level'] == 'N4') $lvlClass = 'bg-n4';
                                        if($g['level'] == 'N3') $lvlClass = 'bg-n3';
                                        if($g['level'] == 'N2') $lvlClass = 'bg-n2';
                                        if($g['level'] == 'N1') $lvlClass = 'bg-n1';
                                    ?>
                                    <span class="badge level-badge <?php echo $lvlClass; ?> rounded-pill"><?php echo htmlspecialchars($g['level']); ?></span>
                                </td>
                                <td><span class="fw-bold fs-5 text-primary jap-text"><?php echo htmlspecialchars($g['structure_name']); ?></span></td>
                                <td class="text-muted text-truncate" style="max-width: 300px;"><?php echo htmlspecialchars($g['meaning']); ?></td>
                                <td><span class="badge bg-light text-dark border"><i class="bi bi-chat-quote text-primary"></i> <?php echo $ex_count; ?> câu</span></td>
                                <td class="text-end pe-4">
                                    <a href="nguphap.php?delete_id=<?php echo $g['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Bạn chắc chắn xóa ngữ pháp này không?');">
                                        <i class="bi bi-trash"></i> Xóa
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
