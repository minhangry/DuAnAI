<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
require_once 'db.php';

$level = $_GET['level'] ?? 'N2';
// Lấy ngẫu nhiên 20 thẻ để học mỗi lượt
$stmt = $pdo->prepare("SELECT * FROM grammar_lessons WHERE level = ? ORDER BY RAND() LIMIT 20");
$stmt->execute([$level]);
$lessons = $stmt->fetchAll();
?>
<?php include 'includes/header.php'; ?>

<style>
    .flashcard-container { perspective: 1000px; max-width: 600px; margin: 0 auto; height: 400px; }
    .flashcard {
        position: relative; width: 100%; height: 100%;
        transition: transform 0.6s cubic-bezier(0.4, 0, 0.2, 1);
        transform-style: preserve-3d; cursor: pointer;
    }
    .flashcard.is-flipped { transform: rotateY(180deg); }
    
    .flashcard-front, .flashcard-back {
        position: absolute; width: 100%; height: 100%;
        backface-visibility: hidden; border-radius: 25px;
        display: flex; flex-direction: column; align-items: center; justify-content: center;
        padding: 40px; box-shadow: 0 15px 35px rgba(0,0,0,0.1); border: 1px solid #e2e8f0;
    }

    .flashcard-front { background: white; color: var(--primary-color); }
    .flashcard-back { 
        background: var(--primary-gradient); color: white; 
        transform: rotateY(180deg); overflow-y: auto; justify-content: start;
    }

    .card-indicator { font-size: 0.9rem; color: #64748b; font-weight: 600; }
    .instruction { font-size: 0.8rem; color: #94a3b8; margin-top: 15px; }
    .flashcard-back b, .flashcard-back strong { color: #fef08a; }
    .flashcard-back img { max-width: 100%; border-radius: 10px; margin-top: 10px; }
    
    .control-btn {
        width: 50px; height: 50px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        background: white; border: 1px solid #e2e8f0; color: var(--primary-color);
        transition: all 0.2s; box-shadow: 0 4px 6px rgba(0,0,0,0.05);
    }
    .control-btn:hover { background: var(--primary-color); color: white; transform: scale(1.1); }
    .control-btn:disabled { opacity: 0.3; cursor: not-allowed; }
</style>

<div class="container py-5">
    <div class="text-center mb-5">
        <h2 class="fw-bold text-primary">Thẻ ghi nhớ thông minh</h2>
        <div class="d-flex justify-content-center gap-2 mt-3">
            <?php foreach(['N5', 'N4', 'N3', 'N2'] as $lv): ?>
                <a href="?level=<?php echo $lv; ?>" class="btn btn-sm <?php echo $level === $lv ? 'btn-primary' : 'btn-outline-primary'; ?> rounded-pill px-3">
                    <?php echo $lv; ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <?php if (empty($lessons)): ?>
        <div class="alert alert-info text-center rounded-4 border-0 shadow-sm">
            Chưa có dữ liệu thẻ cho trình độ này. Hãy chạy <b>readjson.php</b> để nhập dữ liệu.
        </div>
    <?php else: ?>
        <div class="text-center mb-3 card-indicator">
            Thẻ <span id="current-index">1</span> / <span id="total-cards"><?php echo count($lessons); ?></span>
        </div>

        <div class="flashcard-container mb-5">
            <div class="flashcard" id="card-inner">
                <div class="flashcard-front">
                    <h1 class="display-4 fw-bold mb-3" id="front-text"></h1>
                    <div class="instruction"><i class="bi bi-cursor-fill"></i> Bấm để xem ý nghĩa</div>
                </div>
                <div class="flashcard-back">
                    <h4 class="fw-bold border-bottom border-white border-opacity-25 pb-2 mb-3 w-100 text-center">Giải nghĩa</h4>
                    <div id="back-text" class="w-100 text-start"></div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-center align-items-center gap-4">
            <button class="control-btn" id="prev-btn" onclick="prevCard()"><i class="bi bi-chevron-left"></i></button>
            <button class="btn btn-outline-secondary rounded-pill px-4" onclick="shuffleCards()"><i class="bi bi-shuffle me-2"></i> Trộn thẻ</button>
            <button class="btn btn-success rounded-pill px-4" onclick="masterCard()"><i class="bi bi-check-circle me-2"></i> Đã thuộc</button>
            <button class="control-btn" id="next-btn" onclick="nextCard()"><i class="bi bi-chevron-right"></i></button>
        </div>
    <?php endif; ?>
</div>

<script>
    const lessons = <?php echo json_encode($lessons); ?>;
    let currentIndex = 0;
    const cardInner = document.getElementById('card-inner');
    const frontText = document.getElementById('front-text');
    const backText = document.getElementById('back-text');
    const indexDisplay = document.getElementById('current-index');
    const prevBtn = document.getElementById('prev-btn');
    const nextBtn = document.getElementById('next-btn');

    function updateCard() {
        if (lessons.length === 0) return;
        
        // Reset trạng thái lật trước khi đổi nội dung
        cardInner.classList.remove('is-flipped');
        
        setTimeout(() => {
            const current = lessons[currentIndex];
            frontText.innerText = current.structure_name.replace(/<[^>]*>?/gm, '');
            backText.innerHTML = `
                <div class="mb-3">
                    <small class="text-uppercase opacity-75 d-block">Ý nghĩa & Cách dùng:</small>
                    <div>${current.meaning}</div>
                </div>
                <div class="small">
                    <small class="text-uppercase opacity-75 d-block">Cách kết hợp:</small>
                    <div class="fw-bold">${current.usage_rules}</div>
                </div>
            `;
            indexDisplay.innerText = currentIndex + 1;
            document.getElementById('total-cards').innerText = lessons.length;
            
            prevBtn.disabled = currentIndex === 0;
            nextBtn.disabled = currentIndex === lessons.length - 1;
        }, 150);
    }

    function masterCard() {
        if (lessons.length === 0) return;
        
        // Xoá thẻ hiện tại khỏi mảng
        lessons.splice(currentIndex, 1);
        
        if (lessons.length === 0) {
            // Hiển thị giao diện khi đã thuộc hết
            document.querySelector('.flashcard-container').innerHTML = `
                <div class="text-center p-5 bg-white rounded-4 shadow-sm border">
                    <i class="bi bi-trophy text-warning display-1"></i>
                    <h3 class="fw-bold mt-3 text-primary">Tuyệt vời!</h3>
                    <p class="text-muted">Bạn đã thuộc hết các thẻ trong lượt học này.</p>
                    <button class="btn btn-primary rounded-pill px-4" onclick="location.reload()">Học lượt mới</button>
                </div>
            `;
            document.querySelector('.card-indicator').style.display = 'none';
            document.querySelector('.d-flex.justify-content-center.gap-4').style.display = 'none';
        } else {
            // Nếu xoá thẻ cuối cùng thì lùi index lại
            if (currentIndex >= lessons.length) {
                currentIndex = lessons.length - 1;
            }
            updateCard();
        }
    }

    function nextCard() {
        if (currentIndex < lessons.length - 1) {
            currentIndex++;
            updateCard();
        }
    }

    function prevCard() {
        if (currentIndex > 0) {
            currentIndex--;
            updateCard();
        }
    }

    function shuffleCards() {
        location.reload(); // Cách nhanh nhất để lấy RAND() mới từ server
    }

    // Sự kiện lật thẻ
    cardInner.addEventListener('click', () => {
        cardInner.classList.toggle('is-flipped');
    });

    // Phím tắt bàn phím
    document.addEventListener('keydown', (e) => {
        if (e.code === 'Space') cardInner.click();
        if (e.code === 'ArrowRight') nextCard();
        if (e.code === 'ArrowLeft') prevCard();
    });

    // Khởi tạo thẻ đầu tiên
    if (lessons.length > 0) updateCard();
</script>

<?php include 'includes/footer.php'; ?>