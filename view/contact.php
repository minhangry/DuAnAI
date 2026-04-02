<?php
session_start();
require_once 'db.php';

$success = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ở đây bạn có thể thêm logic gửi mail hoặc lưu vào database
    $success = true;
}
?>
<!-- AOS CSS -->
<link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
<?php include 'includes/header.php'; ?>

<style>
    .contact-hero {
        background: var(--primary-gradient);
        color: white;
        padding: 60px 0;
        border-radius: 0 0 40px 40px;
        margin-top: -1.5rem;
    }
    .contact-info-card {
        border: none;
        border-radius: 20px;
        transition: all 0.3s ease;
    }
    .contact-icon-box {
        width: 50px;
        height: 50px;
        background: rgba(13, 148, 136, 0.1);
        color: var(--primary-color);
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 12px;
        font-size: 1.5rem;
    }
    .form-control:focus {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 0.25rem rgba(13, 148, 136, 0.15);
    }
</style>

<div class="contact-hero text-center mb-5 shadow-sm">
    <div class="container">
        <h1 class="fw-bold display-5">Liên hệ với chúng tôi</h1>
        <p class="lead opacity-90">Chúng tôi luôn sẵn sàng lắng nghe và hỗ trợ hành trình học tiếng Nhật của bạn.</p>
    </div>
</div>

<div class="container py-4">
    <div class="row g-5">
        <!-- Cột thông tin liên hệ -->
        <div class="col-lg-4" data-aos="fade-right">
            <h3 class="fw-bold mb-4">Thông tin hỗ trợ</h3>
            <div class="d-flex mb-4">
                <div class="contact-icon-box me-3"><i class="bi bi-geo-alt"></i></div>
                <div>
                    <h6 class="fw-bold mb-1">Địa chỉ</h6>
                    <p class="text-muted small">Khu Công nghệ cao, Quận 9, TP. Hồ Chí Minh</p>
                </div>
            </div>
            <div class="d-flex mb-4">
                <div class="contact-icon-box me-3"><i class="bi bi-envelope-at"></i></div>
                <div>
                    <h6 class="fw-bold mb-1">Email đường dây nóng</h6>
                    <p class="text-muted small">support@jlpt-ai.edu.vn</p>
                </div>
            </div>
            <div class="d-flex mb-4">
                <div class="contact-icon-box me-3"><i class="bi bi-telephone-outbound"></i></div>
                <div>
                    <h6 class="fw-bold mb-1">Điện thoại</h6>
                    <p class="text-muted small">+84 (028) 1234 5678</p>
                </div>
            </div>
            <hr class="my-4 opacity-10">
            <h6 class="fw-bold mb-3">Theo dõi chúng tôi</h6>
            <div class="d-flex gap-2">
                <a href="#" class="btn btn-outline-primary btn-sm rounded-circle"><i class="bi bi-facebook"></i></a>
                <a href="#" class="btn btn-outline-primary btn-sm rounded-circle"><i class="bi bi-youtube"></i></a>
                <a href="#" class="btn btn-outline-primary btn-sm rounded-circle"><i class="bi bi-tiktok"></i></a>
            </div>
        </div>

        <!-- Cột Form liên hệ -->
        <div class="col-lg-8" data-aos="fade-left">
            <div class="card contact-info-card shadow-lg p-2">
                <div class="card-body p-4">
                    <?php if ($success): ?>
                        <div class="alert alert-success border-0 shadow-sm mb-4">
                            <i class="bi bi-check-circle-fill me-2"></i> Cảm ơn bạn! Tin nhắn của bạn đã được gửi đi thành công.
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Họ và tên</label>
                                <input type="text" class="form-control rounded-3 py-2" placeholder="VD: Nguyễn Văn A" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Địa chỉ Email</label>
                                <input type="email" class="form-control rounded-3 py-2" placeholder="name@example.com" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold">Chủ đề</label>
                                <input type="text" class="form-control rounded-3 py-2" placeholder="Bạn cần hỗ trợ về vấn đề gì?" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold">Nội dung tin nhắn</label>
                                <textarea class="form-control rounded-3" rows="5" placeholder="Nhập nội dung chi tiết tại đây..." required></textarea>
                            </div>
                            <div class="col-12 text-end mt-4">
                                <button type="submit" class="btn btn-primary btn-lg px-5 rounded-pill shadow">
                                    Gửi tin nhắn <i class="bi bi-send-fill ms-2"></i>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- AOS JS -->
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
  AOS.init({ duration: 800, once: true });
</script>
<?php include 'includes/footer.php'; ?>