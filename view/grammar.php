<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
require_once 'db.php';

$level = $_GET['level'] ?? 'N2';
$search = trim($_GET['search'] ?? '');

$query = "SELECT * FROM grammar_lessons WHERE level = ?";
$params = [$level];

if ($search !== '') {
    $query .= " AND (structure_name LIKE ? OR meaning LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$query .= " ORDER BY structure_name ASC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$lessons = $stmt->fetchAll();
?>
<?php include 'includes/header.php'; ?>

<style>
    .grammar-card { border-radius: 12px; transition: all 0.3s ease; border: 1px solid #e0e6ed; }
    .grammar-card:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.1) !important; }
    .search-box { border-radius: 30px; padding-left: 20px; }
    .example-list li { border-left: 3px solid #0d6efd; padding-left: 12px; margin-bottom: 12px; line-height: 1.6; }
    .usage-label { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; color: #6c757d; font-weight: 700; }
    .grammar-card img { max-width: 100%; height: auto; border-radius: 8px; margin-top: 10px; display: block; }
    .meaning-text { line-height: 1.6; }
</style>

<div class="container py-4">
    <div class="text-center mb-5">
        <h2 class="fw-bold text-primary">Thư viện Ngữ pháp <span class="badge bg-primary rounded-pill"><?php echo htmlspecialchars($level); ?></span></h2>
        <p class="text-muted">Tổng hợp kiến thức ngữ pháp JLPT từ các nguồn tin cậy</p>
    </div>

    <div class="row justify-content-center mb-5">
        <div class="col-md-8">
            <form method="GET" class="d-flex gap-2">
                <input type="hidden" name="level" value="<?php echo htmlspecialchars($level); ?>">
                <input type="text" name="search" class="form-control search-box shadow-sm" placeholder="Tìm kiếm cấu trúc hoặc ý nghĩa..." value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="btn btn-primary px-4 rounded-pill shadow-sm">Tìm kiếm</button>
            </form>
        </div>
    </div>
    
    <div class="d-flex justify-content-center mb-5 gap-2 flex-wrap">
        <?php foreach(['N5', 'N4', 'N3', 'N2'] as $lv): ?>
            <a href="?level=<?php echo $lv; ?>&search=<?php echo urlencode($search); ?>" class="btn <?php echo $level === $lv ? 'btn-primary shadow' : 'btn-outline-primary'; ?> px-4 rounded-pill fw-bold">
                <?php echo $lv; ?>
            </a>
        <?php endforeach; ?>
    </div>

    <div class="row g-4">
        <?php if (empty($lessons)): ?>
            <div class="col-12 text-center py-5">
                <div class="text-muted mb-3"><i class="bi bi-emoji-frown fs-1"></i></div>
                <h4 class="text-muted">Không tìm thấy ngữ pháp nào</h4>
                <p>Thử tìm kiếm với từ khóa khác hoặc chuyển cấp độ nhé!</p>
            </div>
        <?php else: ?>
            <?php foreach ($lessons as $l): ?>
                <div class="col-md-6">
                    <div class="card grammar-card shadow-sm h-100 border-0 bg-white">
                        <div class="card-body p-4 d-flex flex-column">
                            <h4 class="text-primary fw-bold mb-3">【<?php echo strip_tags($l['structure_name'], '<b><i><strong>'); ?>】</h4>
                            
                            <div class="mb-3">
                                <div class="usage-label mb-1">Cách dùng</div>
                                <p class="mb-0 text-dark"><?php echo strip_tags($l['usage_rules'], '<b><i><strong><br>'); ?></p>
                            </div>

                            <div class="bg-primary-subtle p-3 rounded-3 mb-4 flex-grow-1">
                                <div class="usage-label mb-1 text-primary">Ý nghĩa</div>
                                <p class="mb-0 fw-semibold meaning-text"><?php echo strip_tags($l['meaning'], '<b><i><strong><br><img>'); ?></p>
                            </div>

                            <?php if ($l['examples']): ?>
                                <div class="mt-auto">
                                    <div class="usage-label mb-2">Ví dụ tiêu biểu</div>
                                    <ul class="list-unstyled example-list mb-0 small text-muted">
                                        <?php 
                                        // Sử dụng true để giải mã thành mảng Associative Array thay vì stdClass Object
                                        $examples = json_decode($l['examples'], true);
                                        if (is_array($examples)):
                                            foreach (array_slice($examples, 0, 3) as $ex): 
                                                // Nếu là định dạng mới có cặp ja - vi
                                                if (is_array($ex) && isset($ex['ja'])): ?>
                                                    <li>
                                                        <div class="jap-text text-dark fw-bold"><?php echo strip_tags($ex['ja'], '<b><strong>'); ?></div>
                                                        <div class="fst-italic"><?php echo strip_tags($ex['vi'] ?? '', '<b><strong>'); ?></div>
                                                    </li>
                                                <?php else: 
                                                    // Nếu là định dạng cũ chỉ có chuỗi String
                                                    $exStr = is_string($ex) ? $ex : json_encode($ex, JSON_UNESCAPED_UNICODE);
                                                ?>
                                                    <li><?php echo strip_tags($exStr, '<b><strong>'); ?></li>
                                                <?php endif;
                                            endforeach; 
                                        endif; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
<?php include 'includes/footer.php'; ?>