<?php
session_start();
$db_path = '../view/db.php';
if (file_exists($db_path)) {
    require_once $db_path;
} elseif (file_exists('../db.php')) {
    require_once '../db.php';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_topic'])) {
    $topic_name = trim($_POST['topic_name']);
    $level = $_POST['level'];
    
    // Arrays of words
    $words = $_POST['words'] ?? [];
    $readings = $_POST['readings'] ?? [];
    $meanings = $_POST['meanings'] ?? [];
    $examples = $_POST['examples'] ?? [];

    try {
        $pdo->beginTransaction();

        // 1. Insert Topic
        $stmtTopic = $pdo->prepare("INSERT INTO flashcard_topics (name, level) VALUES (?, ?)");
        $stmtTopic->execute([$topic_name, $level]);
        $topic_id = $pdo->lastInsertId();

        // 2. Insert Words limitly
        if (!empty($words) && is_array($words)) {
            $stmtWord = $pdo->prepare("INSERT INTO flashcard_words (topic_id, word, reading, meaning, example) VALUES (?, ?, ?, ?, ?)");
            for ($i = 0; $i < count($words); $i++) {
                $w = trim($words[$i]);
                $r = trim($readings[$i]);
                $m = trim($meanings[$i]);
                $e = trim($examples[$i]);
                
                // Bỏ qua nếu từ vựng và ý nghĩa bị trống
                if (!empty($w) && !empty($m)) {
                    $stmtWord->execute([$topic_id, $w, $r, $m, $e]);
                }
            }
        }

        $pdo->commit();
        $_SESSION['msg'] = "Đã thêm chủ đề '$topic_name' và các từ vựng thành công!";
        header('Location: flashcard.php');
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Đã có lỗi xảy ra: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Thêm Chủ đề Flashcard - JLPT AI ADMIN</title>
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
        }
        .navbar { background: var(--primary-gradient) !important; border-bottom: 3px solid var(--accent-color); }
        .navbar-brand { font-weight: 800; color: #ffffff !important; }
        .nav-link { font-weight: 500; color: #e0f2fe !important; }
        .nav-link:hover, .nav-link.active { color: #ffffff !important; }
        .card { border-radius: 12px; border: none; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        .word-row { background-color: #fdfdfd; padding: 15px; border-radius: 8px; border: 1px solid #e1e4e8; margin-bottom: 15px; position: relative;}
        .remove-row { position: absolute; top: 10px; right: 10px; color: #dc3545; cursor: pointer; }
    </style>
</head>
<body>

<?php include 'headadm_nav.php'; ?>

<div class="container py-4">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="flashcard.php">Quản lý Flashcard</a></li>
            <li class="breadcrumb-item active" aria-current="page">Thêm Chủ đề mới</li>
        </ol>
    </nav>

    <div class="row justify-content-center">
        <div class="col-lg-9">
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><i class="bi bi-exclamation-triangle-fill"></i> <?php echo $error; ?></div>
            <?php endif; ?>

            <form action="" method="POST" id="flashcardForm">
                <!-- Thông tin Chủ đề -->
                <div class="card mb-4 shadow-sm">
                    <div class="card-header bg-primary text-white py-3">
                        <h5 class="fw-bold mb-0"><i class="bi bi-collection"></i> 1. Thông tin Chủ đề</h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="row">
                            <div class="col-md-8 mb-3">
                                <label class="form-label fw-bold">Tên chủ đề <span class="text-danger">*</span></label>
                                <input type="text" name="topic_name" class="form-control" placeholder="VD: Gia đình, Công việc, Nấu ăn..." required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-bold">Cấp độ JLPT</label>
                                <select name="level" class="form-select">
                                    <option value="N5">N5 (Sơ cấp 1)</option>
                                    <option value="N4">N4 (Sơ cấp 2)</option>
                                    <option value="N3" selected>N3 (Trung cấp)</option>
                                    <option value="N2">N2 (Thượng cấp)</option>
                                    <option value="N1">N1 (Cao cấp)</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Danh sách từ vựng -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-info text-white py-3 d-flex justify-content-between align-items-center">
                        <h5 class="fw-bold mb-0"><i class="bi bi-list-ul"></i> 2. Nhập danh sách Từ vựng</h5>
                        <button type="button" class="btn btn-sm btn-light fw-bold text-info" id="addWordBtn">
                            <i class="bi bi-plus-lg"></i> Thêm ô từ vựng
                        </button>
                    </div>
                    <div class="card-body p-4 bg-light" id="wordsContainer">
                        <!-- Mặc định có 2 ô từ vựng -->
                        <div class="word-row">
                            <h6 class="text-muted fw-bold mb-3 border-bottom pb-2">Từ vựng #1</h6>
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <label class="form-label small text-muted mb-1">Kanji/Từ <span class="text-danger">*</span></label>
                                    <input type="text" name="words[]" class="form-control" required placeholder="VD: 家族">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small text-muted mb-1">Cách đọc <span class="text-danger">*</span></label>
                                    <input type="text" name="readings[]" class="form-control" required placeholder="VD: かぞく">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small text-muted mb-1">Ý nghĩa (Tiếng Việt) <span class="text-danger">*</span></label>
                                    <input type="text" name="meanings[]" class="form-control" required placeholder="VD: Gia đình">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small text-muted mb-1">Ví dụ</label>
                                    <input type="text" name="examples[]" class="form-control" placeholder="VD: 家族と住む">
                                </div>
                            </div>
                        </div>
                        <div class="word-row">
                            <i class="bi bi-x-circle remove-row fs-5" title="Xóa ô này"></i>
                            <h6 class="text-muted fw-bold mb-3 border-bottom pb-2">Từ vựng #2</h6>
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <input type="text" name="words[]" class="form-control" placeholder="Kanji/Từ">
                                </div>
                                <div class="col-md-3">
                                    <input type="text" name="readings[]" class="form-control" placeholder="Cách đọc">
                                </div>
                                <div class="col-md-3">
                                    <input type="text" name="meanings[]" class="form-control" placeholder="Ý nghĩa">
                                </div>
                                <div class="col-md-3">
                                    <input type="text" name="examples[]" class="form-control" placeholder="Ví dụ">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-grid gap-2 mb-5">
                    <button type="submit" name="submit_topic" class="btn btn-success btn-lg fw-bold" style="background: var(--primary-gradient); border:none;">
                        <i class="bi bi-save2"></i> Lưu Chủ đề & Các Từ vựng
                    </button>
                    <a href="flashcard.php" class="btn btn-outline-secondary">Hủy bỏ</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const container = document.getElementById('wordsContainer');
        const addBtn = document.getElementById('addWordBtn');
        let wordCount = 2; // Bắt đầu từ 2 vì đã có 2 sẵn

        // Thêm mới
        addBtn.addEventListener('click', function() {
            wordCount++;
            const newRow = document.createElement('div');
            newRow.className = 'word-row';
            newRow.innerHTML = `
                <i class="bi bi-x-circle remove-row fs-5" title="Xóa ô này"></i>
                <h6 class="text-muted fw-bold mb-3 border-bottom pb-2">Từ vựng #${wordCount}</h6>
                <div class="row g-3">
                    <div class="col-md-3"><input type="text" name="words[]" class="form-control" placeholder="Kanji/Từ"></div>
                    <div class="col-md-3"><input type="text" name="readings[]" class="form-control" placeholder="Cách đọc"></div>
                    <div class="col-md-3"><input type="text" name="meanings[]" class="form-control" placeholder="Ý nghĩa"></div>
                    <div class="col-md-3"><input type="text" name="examples[]" class="form-control" placeholder="Ví dụ"></div>
                </div>
            `;
            container.appendChild(newRow);
        });

        // Xóa (Sử dụng Event Delegation để gắn sự kiện cho các nút Xóa được tạo động)
        container.addEventListener('click', function(e) {
            if (e.target.classList.contains('remove-row')) {
                const row = e.target.closest('.word-row');
                // Không cho phép xóa hết, giữ lại ít nhất 1 hàng
                if (container.querySelectorAll('.word-row').length > 1) {
                    row.remove();
                } else {
                    alert('Bạn cần giữ lại ít nhất 1 từ vựng.');
                }
            }
        });
    });
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
