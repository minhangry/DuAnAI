<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
require_once 'db.php';

$user_id = $_SESSION['user_id'];

// Lấy thống kê tổng quan
$stmt_stats = $pdo->prepare("SELECT 
    COUNT(*) as total_exams, 
    AVG(score) as avg_score, 
    MAX(score) as max_score 
    FROM exam_results WHERE user_id = ?");
$stmt_stats->execute([$user_id]);
$stats = $stmt_stats->fetch();

// Lấy danh sách lịch sử thi gần đây
$stmt_history = $pdo->prepare("SELECT * FROM exam_results WHERE user_id = ? ORDER BY exam_date DESC LIMIT 10");
$stmt_history->execute([$user_id]);
$history = $stmt_history->fetchAll();

// Chuẩn bị dữ liệu cho biểu đồ (lấy 15 bài thi gần nhất theo thứ tự thời gian tăng dần)
$stmt_chart = $pdo->prepare("SELECT score, exam_date FROM exam_results WHERE user_id = ? ORDER BY exam_date ASC LIMIT 15");
$stmt_chart->execute([$user_id]);
$chart_results = $stmt_chart->fetchAll();
$labels = [];
$scores = [];
foreach ($chart_results as $row) {
    $labels[] = date('d/m', strtotime($row['exam_date']));
    $scores[] = (float)$row['score'];
}
?>
<?php include 'includes/header.php'; ?>

<div class="container py-4">
    <div class="mb-5">
        <h2 class="fw-bold">Bảng điều khiển học tập</h2>
        <p class="text-muted">Chào mừng trở lại, <?php echo htmlspecialchars($_SESSION['username']); ?>! Theo dõi tiến độ của bạn tại đây.</p>
    </div>

    <!-- Thống kê tổng quan -->
    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <div class="card h-100 shadow-sm border-0 border-start border-primary border-5">
                <div class="card-body p-4">
                    <div class="text-muted small fw-bold text-uppercase">Tổng số bài thi</div>
                    <div class="h2 fw-bold mt-2 text-primary"><?php echo $stats['total_exams'] ?? 0; ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100 shadow-sm border-0 border-start border-success border-5">
                <div class="card-body p-4">
                    <div class="text-muted small fw-bold text-uppercase">Điểm trung bình</div>
                    <div class="h2 fw-bold mt-2 text-success"><?php echo number_format($stats['avg_score'] ?? 0, 1); ?>%</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100 shadow-sm border-0 border-start border-warning border-5">
                <div class="card-body p-4">
                    <div class="text-muted small fw-bold text-uppercase">Điểm cao nhất</div>
                    <div class="h2 fw-bold mt-2 text-warning"><?php echo number_format($stats['max_score'] ?? 0, 1); ?>%</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Biểu đồ tiến độ điểm số -->
    <div class="card shadow-sm border-0 mb-5">
        <div class="card-header bg-white py-4 border-0 pb-0">
            <h5 class="fw-bold mb-0"><i class="bi bi-graph-up-arrow"></i> Xu hướng điểm số (%)</h5>
        </div>
        <div class="card-body p-4">
            <div style="height: 300px; width: 100%;">
                <canvas id="scoreChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Lịch sử thi gần đây -->
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-4 border-0">
            <h5 class="fw-bold mb-0"><i class="bi bi-clock-history"></i> Lịch sử thi gần đây</h5>
        </div>
        <div class="table-responsive px-4 pb-4">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Ngày thi</th>
                        <th class="text-center">Số câu đúng</th>
                        <th class="text-center">Số câu sai</th>
                        <th class="text-center">Điểm số</th>
                        <th>Trạng thái</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($history)): ?>
                        <tr>
                            <td colspan="5" class="text-center py-5 text-muted">Bạn chưa thực hiện bài thi nào. <a href="quiz.php" class="fw-bold">Bắt đầu ngay!</a></td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($history as $row): ?>
                            <tr>
                                <td><?php echo date('d/m/Y H:i', strtotime($row['exam_date'])); ?></td>
                                <td class="text-center text-success fw-bold"><?php echo $row['total_correct']; ?></td>
                                <td class="text-center text-danger"><?php echo $row['total_incorrect']; ?></td>
                                <td class="text-center fw-bold text-primary"><?php echo $row['score']; ?>%</td>
                                <td>
                                    <span class="badge <?php echo $row['score'] >= 50 ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger'; ?> px-3 py-2">
                                        <?php echo $row['score'] >= 50 ? 'Đạt' : 'Cần cố gắng'; ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('scoreChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($labels); ?>,
            datasets: [{
                label: 'Điểm số (%)',
                data: <?php echo json_encode($scores); ?>,
                borderColor: '#0d9488',
                backgroundColor: 'rgba(13, 148, 136, 0.1)',
                borderWidth: 3,
                tension: 0.4,
                fill: true,
                pointBackgroundColor: '#0d9488',
                pointRadius: 4,
                pointHoverRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100,
                    ticks: {
                        callback: function(value) { return value + '%'; }
                    }
                }
            },
            plugins: {
                legend: { display: false }
            }
        }
    });
});
</script>
<?php include 'includes/footer.php'; ?>