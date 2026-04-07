<?php
session_start();
// Yêu cầu kết nối DB để lấy thông số (ví dụ: ../db.php nếu có)
// Tuy nhiên để tương thích với cấu trúc của bạn, tôi sẽ giả định file db.php nằm ở ngoài hoặc có thể chưa cần đến vì mình đang mock.
$db_path = '../db.php';
if (file_exists($db_path)) {
    require_once $db_path;
}

// Bắt block try-catch nếu db chưa có dữ liệu hoặc lỗi
try {
    if (isset($pdo)) {
        // Lấy số lượng user
        $stmt_users = $pdo->query("SELECT COUNT(*) as total FROM users");
        $total_users = $stmt_users->fetch()['total'] ?? 0;

        // Lấy số lượng bài thi
        $stmt_exams = $pdo->query("SELECT COUNT(*) as total FROM exam_results");
        $total_exams = $stmt_exams->fetch()['total'] ?? 0;

        // Lấy ngân hàng câu hỏi
        $stmt_questions = $pdo->query("SELECT COUNT(*) as total FROM questions");
        $total_questions = $stmt_questions->fetch()['total'] ?? 0;
    } else {
        $total_users = 0;
        $total_exams = 0;
        $total_questions = 0;
    }
} catch (Exception $e) {
    $total_users = 0;
    $total_exams = 0;
    $total_questions = 0;
}

// Giả lập doanh thu: giả định mỗi user tham gia khoá học/luyện thi là 500,000đ
$mock_revenue = $total_users > 0 ? $total_users * 500000 : 15500000; 
if ($total_users == 0) {
    $total_users = 31; // Mock dữ liệu cho có nếu chưa có db
    $total_exams = 128;
    $total_questions = 850;
}

// Mock dữ liệu biểu đồ doanh thu 7 ngày gần nhất
$labels = [];
$revenues = [];
for ($i = 6; $i >= 0; $i--) {
    $labels[] = date('d/m', strtotime("-$i days"));
    $revenues[] = rand(1000000, 5000000);
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Dashboard - JLPT AI</title>
    <!-- Thư viện Bootstrap & Icons đồng bộ với view -->
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
    </style>
</head>
<body>
<!-- Navbar cho Admin -->
<?php include 'headadm_nav.php'; ?>

<div class="container py-4">
    <div class="mb-5">
        <h2 class="fw-bold">Bảng điều khiển Quản trị viên</h2>
        <p class="text-muted">Tổng quan hệ thống, thống kê người dùng và doanh thu của website.</p>
    </div>

    <!-- Thống kê tổng quan - 4 cột -->
    <div class="row g-4 mb-5">
        <div class="col-md-3">
            <div class="card h-100 shadow-sm border-0 border-start border-primary border-5">
                <div class="card-body p-4">
                    <div class="text-muted small fw-bold text-uppercase"><i class="bi bi-people-fill"></i> Tổng Users</div>
                    <div class="h2 fw-bold mt-2 text-primary"><?php echo number_format($total_users); ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100 shadow-sm border-0 border-start border-success border-5">
                <div class="card-body p-4">
                    <div class="text-muted small fw-bold text-uppercase"><i class="bi bi-cash-stack"></i> Doanh Thu</div>
                    <div class="h2 fw-bold mt-2 text-success"><?php echo number_format($mock_revenue, 0, ',', '.'); ?>đ</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100 shadow-sm border-0 border-start border-warning border-5">
                <div class="card-body p-4">
                    <div class="text-muted small fw-bold text-uppercase"><i class="bi bi-journal-check"></i> Bài Thi Đã Làm</div>
                    <div class="h2 fw-bold mt-2 text-warning"><?php echo number_format($total_exams); ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100 shadow-sm border-0 border-start border-info border-5">
                <div class="card-body p-4">
                    <div class="text-muted small fw-bold text-uppercase"><i class="bi bi-collection-fill"></i> Câu Hỏi</div>
                    <div class="h2 fw-bold mt-2 text-info"><?php echo number_format($total_questions); ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Biểu đồ doanh thu & Hoạt động gần đây -->
    <div class="row mb-5">
        <div class="col-lg-8 mb-4 mb-lg-0">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white py-4 border-0 pb-0">
                    <h5 class="fw-bold mb-0"><i class="bi bi-graph-up-arrow"></i> Biểu đồ doanh thu 7 ngày qua</h5>
                </div>
                <div class="card-body p-4">
                    <!-- ChartJS Container -->
                    <div style="height: 300px; width: 100%;">
                        <canvas id="revenueChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white py-4 border-0">
                    <h5 class="fw-bold mb-0"><i class="bi bi-activity"></i> Hoạt động gần đây</h5>
                </div>
                <div class="card-body p-4 pt-2">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item px-0 py-3 d-flex align-items-center">
                            <i class="bi bi-person-plus text-primary fs-4 me-3"></i>
                            <div>
                                <h6 class="mb-0 fw-bold">User mới đăng ký</h6>
                                <small class="text-muted">Vài phút trước</small>
                            </div>
                        </li>
                        <li class="list-group-item px-0 py-3 d-flex align-items-center">
                            <i class="bi bi-check-circle text-success fs-4 me-3"></i>
                            <div>
                                <h6 class="mb-0 fw-bold">1 Bài thi vừa hoàn thành</h6>
                                <small class="text-muted">15 phút trước</small>
                            </div>
                        </li>
                         <li class="list-group-item px-0 py-3 d-flex align-items-center">
                            <i class="bi bi-cash text-warning fs-4 me-3"></i>
                            <div>
                                <h6 class="mb-0 fw-bold">Thanh toán mới ghi nhận</h6>
                                <small class="text-muted">1 giờ trước</small>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('revenueChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar', // Sang biểu đồ cột theo yêu cầu thông thường về doanh thu
        data: {
            labels: <?php echo json_encode($labels); ?>,
            datasets: [{
                label: 'Doanh thu (VNĐ)',
                data: <?php echo json_encode($revenues); ?>,
                backgroundColor: 'rgba(13, 148, 136, 0.8)',
                borderColor: '#0d9488',
                borderWidth: 1,
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) { 
                            return new Intl.NumberFormat('vi-VN').format(value) + 'đ'; 
                        }
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
</body>
</html>
