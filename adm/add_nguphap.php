<?php
session_start();
$db_path = '../view/db.php';
if (file_exists($db_path)) {
    require_once $db_path;
} elseif (file_exists('../db.php')) {
    require_once '../db.php';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_grammar'])) {
    $structure_name = trim($_POST['structure_name']);
    $level = $_POST['level'];
    $meaning = trim($_POST['meaning']);
    $usage_rules = trim($_POST['usage_rules']);

    // Gom các ví dụ thành mảng JSON
    $jap_examples = $_POST['ex_jap'] ?? [];
    $vie_examples = $_POST['ex_vie'] ?? [];
    
    $examples_array = [];
    for ($i = 0; $i < count($jap_examples); $i++) {
        $j = trim($jap_examples[$i]);
        $v = trim($vie_examples[$i]);
        if (!empty($j) && !empty($v)) {
            $examples_array[] = [
                'ja' => $j,
                'vi' => $v
            ];
        }
    }
    
    // Convert sang JSON String (hỗ trợ Unicode)
    $examples_json = json_encode($examples_array, JSON_UNESCAPED_UNICODE);

    try {
        $stmt = $pdo->prepare("INSERT INTO grammar_lessons (structure_name, meaning, usage_rules, examples, level) VALUES (?, ?, ?, ?, ?)");
        if ($stmt->execute([$structure_name, $meaning, $usage_rules, $examples_json, $level])) {
            $_SESSION['msg'] = "Đã thêm cấu trúc '$structure_name' thành công!";
            header('Location: nguphap.php');
            exit;
        }
    } catch (Exception $e) {
        $error = "Đã có lỗi xảy ra: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Thêm Ngữ pháp - JLPT AI ADMIN</title>
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
        .ex-row { background: #fdfdfd; padding: 15px; border-radius: 8px; border: 1px solid #e1e4e8; margin-bottom: 15px; position: relative; }
        .remove-row { position: absolute; top: 10px; right: 10px; color: #dc3545; cursor: pointer; }
    </style>
</head>
<body>

<?php include 'headadm_nav.php'; ?>

<div class="container py-4">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="nguphap.php" class="text-decoration-none">Quản lý Ngữ pháp</a></li>
            <li class="breadcrumb-item active" aria-current="page">Thêm Cấu trúc mới</li>
        </ol>
    </nav>

    <div class="row justify-content-center">
        <div class="col-lg-9">
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><i class="bi bi-exclamation-triangle-fill"></i> <?php echo $error; ?></div>
            <?php endif; ?>

            <form action="" method="POST" id="grammarForm">
                <!-- Thông tin cơ bản -->
                <div class="card mb-4 shadow-sm border border-primary border-opacity-25">
                    <div class="card-header bg-white py-3 border-bottom border-primary border-opacity-10">
                        <h5 class="fw-bold mb-0 text-primary"><i class="bi bi-info-square"></i> Thông tin Cấu trúc Ngữ pháp</h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label fw-bold">Tên cấu trúc (Tiếng Nhật) <span class="text-danger">*</span></label>
                                <input type="text" name="structure_name" class="form-control form-control-lg" placeholder="VD: ～に違いない" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Cấp độ JLPT</label>
                                <select name="level" class="form-select form-select-lg">
                                    <option value="N5">N5 (Sơ cấp 1)</option>
                                    <option value="N4">N4 (Sơ cấp 2)</option>
                                    <option value="N3" selected>N3 (Trung cấp)</option>
                                    <option value="N2">N2 (Thượng cấp)</option>
                                    <option value="N1">N1 (Cao cấp)</option>
                                </select>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label fw-bold text-muted mt-2">Ý nghĩa (Tiếng Việt) <span class="text-danger">*</span></label>
                                <input type="text" name="meaning" class="form-control" placeholder="VD: Chắc chắn là... / Nhất định là..." required>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label fw-bold text-muted mt-2">Cách chia / Cách dùng <span class="text-danger">*</span></label>
                                <textarea name="usage_rules" class="form-control" rows="2" placeholder="VD: Thể thông thường + に違いない (Danh từ/Tính từ đuôi な không có だ)" required></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Danh sách Câu ví dụ (Lưu dạng JSON) -->
                <div class="card shadow-sm mb-4 border border-info border-opacity-25">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                        <h5 class="fw-bold mb-0 text-info"><i class="bi bi-chat-left-text"></i> Danh sách Câu ví dụ</h5>
                        <button type="button" class="btn btn-sm btn-outline-info fw-bold" id="addExBtn">
                            <i class="bi bi-plus-lg"></i> Thêm ví dụ
                        </button>
                    </div>
                    <div class="card-body p-4 bg-light" id="examplesContainer">
                        <div class="alert alert-secondary py-2 small"><i class="bi bi-info-circle"></i> Vui lòng điền đủ câu Tiếng Nhật và nghĩa Tiếng Việt (Những hàng trống sẽ tự động bỏ qua).</div>
                        
                        <!-- Mặc định có sẵn 2 ô ví dụ -->
                        <div class="ex-row">
                            <h6 class="text-muted fw-bold mb-3 border-bottom pb-2">Ví dụ #1</h6>
                            <div class="row g-2">
                                <div class="col-md-6 mb-2">
                                    <label class="small text-muted mb-1">Mẫu câu (Kanji/Kana) <span class="text-danger">*</span></label>
                                    <textarea name="ex_jap[]" class="form-control" rows="2" placeholder="VD: 彼は犯人に違いない。" required></textarea>
                                </div>
                                <div class="col-md-6 mb-2">
                                    <label class="small text-muted mb-1">Dịch nghĩa <span class="text-danger">*</span></label>
                                    <textarea name="ex_vie[]" class="form-control" rows="2" placeholder="VD: Anh ta chắc chắn là thủ phạm." required></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="ex-row">
                            <i class="bi bi-x-circle remove-row fs-5" title="Xóa ví dụ này"></i>
                            <h6 class="text-muted fw-bold mb-3 border-bottom pb-2">Ví dụ #2</h6>
                            <div class="row g-2">
                                <div class="col-md-6 mb-2">
                                    <textarea name="ex_jap[]" class="form-control" rows="2" placeholder="Mẫu câu Nhật"></textarea>
                                </div>
                                <div class="col-md-6 mb-2">
                                    <textarea name="ex_vie[]" class="form-control" rows="2" placeholder="Nghĩa Việt"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-grid gap-2 mb-5">
                    <button type="submit" name="submit_grammar" class="btn btn-success btn-lg fw-bold" style="background: var(--primary-gradient); border:none;">
                        <i class="bi bi-save"></i> Cập nhật Ngữ pháp vào Cơ sở dữ liệu
                    </button>
                    <a href="nguphap.php" class="btn btn-outline-secondary">Trở về Danh sách</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const container = document.getElementById('examplesContainer');
        const addBtn = document.getElementById('addExBtn');
        let count = 2; 

        addBtn.addEventListener('click', function() {
            count++;
            const newRow = document.createElement('div');
            newRow.className = 'ex-row';
            newRow.innerHTML = `
                <i class="bi bi-x-circle remove-row fs-5" title="Xóa ví dụ này"></i>
                <h6 class="text-muted fw-bold mb-3 border-bottom pb-2">Ví dụ #${count}</h6>
                <div class="row g-2">
                    <div class="col-md-6 mb-2">
                        <textarea name="ex_jap[]" class="form-control" rows="2" placeholder="Mẫu câu Nhật"></textarea>
                    </div>
                    <div class="col-md-6 mb-2">
                        <textarea name="ex_vie[]" class="form-control" rows="2" placeholder="Nghĩa Việt"></textarea>
                    </div>
                </div>
            `;
            container.appendChild(newRow);
        });

        container.addEventListener('click', function(e) {
            if (e.target.classList.contains('remove-row')) {
                const row = e.target.closest('.ex-row');
                if (container.querySelectorAll('.ex-row').length > 1) {
                    row.remove();
                } else {
                    alert('Nên giữ lại ít nhất 1 câu ví dụ!');
                }
            }
        });
    });
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
