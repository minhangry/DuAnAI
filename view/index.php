<?php
session_start();
require_once 'db.php';
?>
<!-- AOS CSS -->
<link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
<?php include 'includes/header.php'; ?>

<style>
    .hero-section {
        background: var(--primary-gradient);
        color: white;
        padding: 100px 0;
        border-radius: 0 0 50px 50px;
        margin-top: -1.5rem;
    }
    .feature-icon {
        width: 60px;
        height: 60px;
        background: rgba(13, 148, 136, 0.1);
        color: var(--primary-color);
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 15px;
        font-size: 1.5rem;
        margin-bottom: 1.5rem;
    }
    .step-number {
        font-size: 3rem;
        font-weight: 800;
        color: rgba(13, 148, 136, 0.1);
        line-height: 1;
    }
    .promo-card {
        background: linear-gradient(45deg, #fbbf24, #f97316);
        border: none;
        color: white;
    }
    .pricing-card {
        border-radius: 20px;
        transition: all 0.3s ease;
    }
    .pricing-card.popular {
        border: 2px solid var(--primary-color);
        transform: scale(1.05);
        z-index: 1;
    }
    .badge-hot { position: absolute; top: -15px; right: 20px; padding: 8px 20px; }
</style>

<!-- Hero Section -->
<section class="hero-section text-center shadow-lg">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <h1 class="display-4 fw-bold mb-4">Chinh phục JLPT cùng Trí tuệ nhân tạo</h1>
                <p class="lead mb-5 opacity-90">Nền tảng học tiếng Nhật thông minh giúp bạn tối ưu hóa lộ trình luyện thi N5 - N2 bằng công nghệ phân tích dữ liệu hiện đại.</p>
                <?php if (!isset($_SESSION['user_id'])): ?>
                    <div class="d-grid gap-3 d-sm-flex justify-content-sm-center">
                        <a href="dangky.php" class="btn btn-light btn-lg px-5 fw-bold text-primary rounded-pill">Bắt đầu miễn phí</a>
                        <a href="login.php" class="btn btn-outline-light btn-lg px-5 rounded-pill">Đăng nhập ngay</a>
                    </div>
                <?php else: ?>
                    <a href="dashboard.php" class="btn btn-light btn-lg px-5 fw-bold text-primary rounded-pill">Vào Dashboard của bạn</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<!-- Stats Section -->
<section class="py-5 bg-white border-bottom" data-aos="fade-up">
    <div class="container">
        <div class="row text-center g-4">
            <div class="col-6 col-md-3" data-aos="fade-up" data-aos-delay="100">
                <div class="h2 fw-bold text-primary mb-0">15,000+</div>
                <div class="text-muted small">Học viên tham gia</div>
            </div>
            <div class="col-6 col-md-3" data-aos="fade-up" data-aos-delay="200">
                <div class="h2 fw-bold text-primary mb-0">5,000+</div>
                <div class="text-muted small">Câu hỏi luyện thi</div>
            </div>
            <div class="col-6 col-md-3" data-aos="fade-up" data-aos-delay="300">
                <div class="h2 fw-bold text-primary mb-0">98%</div>
                <div class="text-muted small">Tỷ lệ hài lòng</div>
            </div>
            <div class="col-6 col-md-3" data-aos="fade-up" data-aos-delay="400">
                <div class="h2 fw-bold text-primary mb-0">24/7</div>
                <div class="text-muted small">Hỗ trợ bởi AI</div>
            </div>
        </div>
    </div>
</section>

<!-- Features Section -->
<section class="py-5 mt-5">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="fw-bold">Tại sao chọn JLPT AI Learning?</h2>
            <p class="text-muted">Chúng tôi mang đến những công cụ tốt nhất cho hành trình học tập của bạn.</p>
        </div>
        <div class="row g-4">
            <div class="col-md-4" data-aos="fade-up" data-aos-delay="100">
                <div class="card h-100 p-4 border-0 shadow-sm">
                    <div class="feature-icon"><i class="bi bi-journal-check"></i></div>
                    <h4 class="fw-bold">Ngân hàng đề thi</h4>
                    <p class="text-muted">Hàng ngàn câu hỏi sát thực tế được phân loại theo cấp độ N2, N3, N4, N5.</p>
                </div>
            </div>
            <div class="col-md-4" data-aos="fade-up" data-aos-delay="200">
                <div class="card h-100 p-4 border-0 shadow-sm">
                    <div class="feature-icon"><i class="bi bi-book"></i></div>
                    <h4 class="fw-bold">Thư viện ngữ pháp</h4>
                    <p class="text-muted">Tra cứu nhanh chóng các cấu trúc ngữ pháp kèm ví dụ chi tiết và hình ảnh minh họa.</p>
                </div>
            </div>
            <div class="col-md-4" data-aos="fade-up" data-aos-delay="300">
                <div class="card h-100 p-4 border-0 shadow-sm">
                    <div class="feature-icon"><i class="bi bi-cpu"></i></div>
                    <h4 class="fw-bold">Phân tích bởi AI</h4>
                    <p class="text-muted">AI sẽ phân tích kết quả thi của bạn để đề xuất lộ trình học tập cá nhân hóa.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Pricing Section (Ads Style) -->
<section class="py-5 bg-light">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="fw-bold">Nâng tầm kiến thức của bạn</h2>
            <p class="text-muted">Chọn gói học tập phù hợp để tăng tốc lộ trình chinh phục JLPT</p>
        </div>
        <div class="row justify-content-center g-4">
            <div class="col-md-4" data-aos="zoom-in" data-aos-delay="100">
                <div class="card pricing-card h-100 shadow-sm border-0">
                    <div class="card-body p-5">
                        <h5 class="fw-bold">Gói Miễn phí</h5>
                        <div class="display-5 fw-bold my-3">0đ</div>
                        <ul class="list-unstyled mb-4 text-muted">
                            <li class="mb-2"><i class="bi bi-check2 text-success me-2"></i>Thư viện ngữ pháp cơ bản</li>
                            <li class="mb-2"><i class="bi bi-check2 text-success me-2"></i>Thi thử 3 lần/ngày</li>
                            <li class="mb-2 text-decoration-line-through"><i class="bi bi-x text-danger me-2"></i>AI phân tích chuyên sâu</li>
                        </ul>
                        <a href="dangky.php" class="btn btn-outline-primary w-100 rounded-pill">Bắt đầu ngay</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4 position-relative" data-aos="zoom-in" data-aos-delay="300">
                <span class="badge bg-danger badge-hot rounded-pill shadow-sm">PHỔ BIẾN NHẤT</span>
                <div class="card pricing-card popular h-100 shadow border-0 bg-white">
                    <div class="card-body p-5">
                        <h5 class="fw-bold text-primary">Gói Premium</h5>
                        <div class="display-5 fw-bold my-3">199k<span class="fs-6 text-muted fw-normal">/tháng</span></div>
                        <ul class="list-unstyled mb-4">
                            <li class="mb-2"><i class="bi bi-check2-all text-primary me-2"></i>Full Thư viện ngữ pháp</li>
                            <li class="mb-2"><i class="bi bi-check2-all text-primary me-2"></i>Không giới hạn bài thi</li>
                            <li class="mb-2"><i class="bi bi-check2-all text-primary me-2"></i>AI phân tích lỗ hổng kiến thức</li>
                            <li class="mb-2"><i class="bi bi-check2-all text-primary me-2"></i>Lộ trình học cá nhân hóa</li>
                        </ul>
                        <button class="btn btn-primary w-100 rounded-pill">Nâng cấp ngay</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Special Offer Banner -->
<section class="py-5">
    <div class="container">
        <div class="card promo-card shadow-lg rounded-4 overflow-hidden" data-aos="flip-up">
            <div class="card-body p-0">
                <div class="row g-0 align-items-center">
                    <div class="col-md-8 p-5">
                        <h2 class="fw-bold mb-3">Ưu đãi giới hạn: Giảm 50% Gói 1 Năm!</h2>
                        <p class="lead mb-4">Chỉ dành cho 100 học viên đăng ký sớm nhất trong tháng này. Đừng bỏ lỡ cơ hội sở hữu lộ trình AI trọn đời.</p>
                        <div class="d-flex gap-3">
                            <button class="btn btn-dark btn-lg px-4 rounded-pill">Nhận ưu đãi ngay</button>
                            <div class="text-white d-flex align-items-center small">
                                <i class="bi bi-clock me-2"></i> Còn lại: 12 ngày 05:42:10
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 d-none d-md-block text-center p-4">
                        <i class="bi bi-gift" style="font-size: 8rem; opacity: 0.5;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- How it works -->
<section class="py-5 bg-white border-top border-bottom">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-6 mb-4 mb-md-0" data-aos="fade-right">
                <img src="https://img.freepik.com/free-vector/online-education-concept_52683-8333.jpg" class="img-fluid rounded-4 shadow-sm" alt="Learning">
            </div>
            <div class="col-md-6 ps-md-5" data-aos="fade-left">
                <h2 class="fw-bold mb-4">Quy trình học tập tối ưu</h2>
                <div class="d-flex mb-4">
                    <div class="step-number me-3">01</div>
                    <div>
                        <h5 class="fw-bold">Đánh giá trình độ</h5>
                        <p class="text-muted">Làm các bài thi thử ngẫu nhiên để xác định điểm mạnh và điểm yếu của bản thân.</p>
                    </div>
                </div>
                <div class="d-flex mb-4">
                    <div class="step-number me-3">02</div>
                    <div>
                        <h5 class="fw-bold">Trau dồi kiến thức</h5>
                        <p class="text-muted">Sử dụng thư viện ngữ pháp thông minh để ôn tập lại những phần còn hổng.</p>
                    </div>
                </div>
                <div class="d-flex">
                    <div class="step-number me-3">03</div>
                    <div>
                        <h5 class="fw-bold">Theo dõi tiến độ</h5>
                        <p class="text-muted">Xem biểu đồ tăng trưởng điểm số tại Dashboard và nhận gợi ý từ AI.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- AOS JS -->
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
  AOS.init({
    duration: 800, // Thời gian chạy hiệu ứng (ms)
    once: true,    // Chỉ chạy hiệu ứng một lần khi cuộn xuống
    offset: 100    // Khoảng cách từ mép màn hình trước khi kích hoạt hiệu ứng
  });
</script>
<?php include 'includes/footer.php'; ?>