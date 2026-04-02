<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
require_once 'db.php';

$user_id = $_SESSION['user_id'];

// Giả định lấy lộ trình AI mới nhất
$stmt = $pdo->prepare("SELECT * FROM ai_roadmaps WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
$stmt->execute([$user_id]);
$roadmap = $stmt->fetch();

// Nếu chưa có dữ liệu thật, ta tạo dữ liệu mẫu để hiển thị giao diện
$steps = $roadmap ? json_decode($roadmap['roadmap_content'], true) : [
    ['title' => 'Củng cố Trợ từ N3', 'status' => 'Hoàn thành', 'desc' => 'Bạn đã sai 3 câu về trợ từ に và で.'],
    ['title' => 'Ngữ pháp chỉ nguyên nhân', 'status' => 'Đang học', 'desc' => 'Tập trung vào cấu trúc ～ばかりに và ～おかげで.'],
    ['title' => 'Luyện đề tổng hợp', 'status' => 'Chưa bắt đầu', 'desc' => 'Làm bài thi thử N3 số 05 sau khi hoàn thành ôn tập.']
];
?>
<?php include 'includes/header.php'; ?>

<style>
    .roadmap-container { position: relative; padding-left: 40px; }
    .roadmap-container::before {
        content: ''; position: absolute; left: 15px; top: 0; bottom: 0;
        width: 4px; background: #e2e8f0; border-radius: 2px;
    }
    .roadmap-step { position: relative; margin-bottom: 40px; }
    .step-dot {
        position: absolute; left: -34px; top: 5px;
        width: 24px; height: 24px; border-radius: 50%;
        border: 4px solid white; box-shadow: 0 0 0 4px #e2e8f0;
        z-index: 2;
    }
    .status-done { background-color: #10b981; box-shadow: 0 0 0 4px #d1fae5; }
    .status-doing { background-color: var(--primary-color); box-shadow: 0 0 0 4px #e0f2fe; }
    .status-todo { background-color: #cbd5e1; }
    .roadmap-step .card { transition: all 0.3s ease; cursor: default; }
    .roadmap-step .card:hover { transform: translateX(10px); border-left: 5px solid var(--primary-color) !important; }
</style>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="text-center mb-5">
                <h2 class="fw-bold text-primary"><i class="bi bi-magic"></i> Lộ trình AI đề xuất</h2>
                <p class="text-muted">Dựa trên kết quả bài thi của bạn, AI đã thiết kế lộ trình tối ưu sau:</p>
            </div>

            <div class="roadmap-container">
                <?php foreach ($steps as $s): ?>
                    <div class="roadmap-step" data-aos="fade-left">
                        <?php 
                            $dotClass = 'status-todo';
                            if ($s['status'] == 'Hoàn thành') $dotClass = 'status-done';
                            if ($s['status'] == 'Đang học') $dotClass = 'status-doing';
                        ?>
                        <div class="step-dot <?php echo $dotClass; ?>"></div>
                        <div class="card shadow-sm border-0 border-start border-4 <?php echo $s['status'] == 'Hoàn thành' ? 'border-success' : ($s['status'] == 'Đang học' ? 'border-primary' : 'border-secondary'); ?>">
                            <div class="card-body p-4">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h5 class="fw-bold mb-0"><?php echo htmlspecialchars($s['title']); ?></h5>
                                    <span class="badge <?php echo $s['status'] == 'Hoàn thành' ? 'bg-success-subtle text-success' : ($s['status'] == 'Đang học' ? 'bg-primary-subtle text-primary' : 'bg-secondary-subtle text-secondary'); ?> rounded-pill px-3">
                                        <?php echo $s['status']; ?>
                                    </span>
                                </div>
                                <p class="text-muted mb-0 small"><?php echo htmlspecialchars($s['desc']); ?></p>
                                <?php if ($s['status'] == 'Đang học'): ?>
                                    <a href="grammar.php" class="btn btn-sm btn-outline-primary mt-3 rounded-pill">Học ngay bài này</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="text-center mt-5">
                <button class="btn btn-primary btn-lg rounded-pill px-5 shadow-sm">
                    <i class="bi bi-arrow-repeat me-2"></i> Yêu cầu AI cập nhật lộ trình mới
                </button>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
