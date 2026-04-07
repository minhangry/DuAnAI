<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
require_once 'db.php';

$level = $_GET['level'] ?? 'N3';
<<<<<<< HEAD
// Lấy ngẫu nhiên 20 thẻ để học mỗi lượt
$stmt = $pdo->prepare("SELECT * FROM flashcards WHERE level = ? ORDER BY RAND() LIMIT 20");
$stmt->execute([$level]);
$flashcards = $stmt->fetchAll();
=======
$topic_id = $_GET['topic_id'] ?? null;

$topics = [];
$words = [];

if ($topic_id) {
    // Lấy thông tin chủ đề và danh sách từ vựng
    $stmt_topic = $pdo->prepare("SELECT * FROM flashcard_topics WHERE id = ?");
    $stmt_topic->execute([$topic_id]);
    $current_topic = $stmt_topic->fetch();

    if ($current_topic) {
        $stmt_words = $pdo->prepare("SELECT * FROM flashcard_words WHERE topic_id = ?");
        $stmt_words->execute([$topic_id]);
        $words = $stmt_words->fetchAll();
    }
} else {
    // Lấy danh sách các chủ đề theo cấp độ
    $stmt_topics = $pdo->prepare("SELECT * FROM flashcard_topics WHERE level = ? ORDER BY created_at DESC");
    $stmt_topics->execute([$level]);
    $topics = $stmt_topics->fetchAll();
}
>>>>>>> 484deb05ac9f85d44008acad1a9ef4d65753e3b0
?>
<?php include 'includes/header.php'; ?>

<style>
    :root {
        --flashcard-shadow: 0 10px 30px rgba(0,0,0,0.1);
        --card-bg: #ffffff;
    }
    
    .topic-card {
        border-radius: 20px;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        border: 2px solid transparent;
        background: white;
    }
    .topic-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 15px 35px rgba(13, 148, 136, 0.15);
        border-color: var(--primary-color);
        cursor: pointer;
    }

    /* Flashcard CSS */
    .flashcard-container { perspective: 1000px; max-width: 550px; margin: 0 auto; height: 380px; }
    .flashcard {
        position: relative; width: 100%; height: 100%;
        transition: transform 0.6s cubic-bezier(0.4, 0, 0.2, 1);
        transform-style: preserve-3d; cursor: pointer;
    }
    .flashcard.is-flipped { transform: rotateY(180deg); }
    
    .flashcard-front, .flashcard-back {
        position: absolute; width: 100%; height: 100%;
        backface-visibility: hidden; border-radius: 30px;
        display: flex; flex-direction: column; align-items: center; justify-content: center;
        padding: 30px; box-shadow: var(--flashcard-shadow); border: 1px solid #f1f5f9;
        text-align: center;
    }

    .flashcard-front { background: white; color: var(--text-main); }
    .flashcard-back { 
        background: var(--primary-gradient); color: white; 
        transform: rotateY(180deg);
    }

    .kana-hint { font-size: 1.2rem; color: #94a3b8; font-family: 'Noto Sans JP', sans-serif; margin-bottom: 5px; }
    .main-text { font-size: 3.5rem; font-weight: 800; font-family: 'Noto Sans JP', sans-serif; color: var(--primary-color); }
    .flashcard-back .main-text { color: white; font-size: 2.5rem; }
    .example-box { font-size: 0.95rem; opacity: 0.9; background: rgba(255,255,255,0.1); padding: 15px; border-radius: 15px; width: 100%; margin-top: 15px; }

    .control-btn {
        width: 60px; height: 60px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        background: white; border: none; font-size: 1.3rem;
        transition: all 0.2s; box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    }
    .control-btn:hover { transform: scale(1.1); }
    .btn-not-mastered { color: #f59e0b; }
    .btn-not-mastered:hover { background: #f59e0b; color: white; }
    .btn-mastered { color: #10b981; }
    .btn-mastered:hover { background: #10b981; color: white; }

    .settings-bar {
        background: white;
        border-radius: 15px;
        padding: 10px 20px;
        margin-bottom: 25px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.02);
    }

    .progress-bar-custom { height: 10px; border-radius: 5px; background: #e2e8f0; overflow: hidden; margin-top: 10px; }
    .progress-inner { height: 100%; background: var(--primary-gradient); transition: width 0.4s ease; }
    
    .learning-status { font-size: 0.85rem; font-weight: 600; color: #64748b; }
</style>

<<<<<<< HEAD
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

    <?php if (empty($flashcards)): ?>
        <div class="alert alert-info text-center rounded-4 border-0 shadow-sm">
            Chưa có dữ liệu thẻ cho trình độ này. Hãy chạy <b>import_flashcards.php</b> để nhập dữ liệu.
        </div>
    <?php else: ?>
        <div class="text-center mb-3 card-indicator">
            Thẻ <span id="current-index">1</span> / <span id="total-cards"><?php echo count($flashcards); ?></span>
        </div>

        <div class="flashcard-container mb-5">
            <div class="flashcard" id="card-inner">
                <div class="flashcard-front">
                    <h1 class="display-2 fw-bold mb-3" id="front-text"></h1>
                    <div class="instruction"><i class="bi bi-cursor-fill"></i> Bấm để xem ý nghĩa</div>
                </div>
                <div class="flashcard-back">
                    <h4 class="fw-bold border-bottom border-white border-opacity-25 pb-2 mb-3 w-100 text-center">Giải nghĩa</h4>
                    <div id="back-text" class="w-100 text-start"></div>
                </div>
=======
<div class="container py-4">
    <?php if (!$topic_id): ?>
        <!-- Giao diện chọn Chủ đề -->
        <div class="mb-5 text-center">
            <h2 class="fw-bold display-6 mb-2">Thẻ ghi nhớ (Flashcards)</h2>
            <p class="text-muted">Chọn một chủ đề để bắt đầu học từ vựng mới!</p>
            
            <div class="d-flex justify-content-center gap-2 mt-4 flex-wrap">
                <?php foreach(['N5', 'N4', 'N3', 'N2'] as $lv): ?>
                    <a href="?level=<?php echo $lv; ?>" class="btn px-4 rounded-pill fw-bold <?php echo $level === $lv ? 'btn-primary' : 'btn-outline-primary'; ?>">
                        <?php echo $lv; ?>
                    </a>
                <?php endforeach; ?>
>>>>>>> 484deb05ac9f85d44008acad1a9ef4d65753e3b0
            </div>
        </div>

        <?php if (empty($topics)): ?>
            <div class="text-center py-5">
                <img src="https://cdni.iconscout.com/illustration/premium/thumb/empty-folder-5074211-4228414.png" style="width: 200px;" alt="">
                <h4 class="text-muted mt-3">Chưa có chủ đề nào cho cấp độ <?php echo $level; ?>.</h4>
                <p class="text-muted">Vui lòng quay lại sau hoặc liên hệ Admin.</p>
            </div>
        <?php else: ?>
            <div class="row g-4 justify-content-center">
                <?php foreach($topics as $t): ?>
                    <div class="col-md-6 col-lg-4">
                        <a href="?topic_id=<?php echo $t['id']; ?>" class="text-decoration-none">
                            <div class="topic-card p-4 shadow-sm h-100 flex-column d-flex">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <span class="badge bg-primary-subtle text-primary rounded-pill px-3 py-2"><?php echo $level; ?></span>
                                    <i class="bi bi-collection-play fs-4 text-primary"></i>
                                </div>
                                <h4 class="fw-bold text-dark mb-2"><?php echo htmlspecialchars($t['name']); ?></h4>
                                <?php
                                    $stmt_count = $pdo->prepare("SELECT COUNT(*) as total FROM flashcard_words WHERE topic_id = ?");
                                    $stmt_count->execute([$t['id']]);
                                    $count = $stmt_count->fetch()['total'];
                                ?>
                                <p class="text-muted mt-auto mb-0"><i class="bi bi-layer-backward"></i> <?php echo $count; ?> từ vựng</p>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    <?php else: ?>
        <!-- Giao diện học Flashcard -->
        <div class="max-width-800 mx-auto">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <a href="flashcard.php?level=<?php echo $level; ?>" class="btn btn-link text-decoration-none p-0 text-muted fw-bold">
                    <i class="bi bi-arrow-left"></i> Quay lại
                </a>
                <h5 class="fw-bold mb-0 text-primary"><?php echo htmlspecialchars($current_topic['name']); ?></h5>
                <div class="learning-status">
                    Đã thuộc: <span id="mastered-count" class="text-success">0</span>/<span id="total-words-count"><?php echo count($words); ?></span>
                </div>
            </div>

            <!-- Settings Bar -->
            <div class="settings-bar d-flex justify-content-between align-items-center shadow-sm">
                <div class="d-flex gap-3">
                    <button class="btn btn-sm btn-outline-secondary rounded-pill px-3 fw-bold" onclick="shuffleQueue()">
                        <i class="bi bi-shuffle me-1"></i> Trộn thẻ
                    </button>
                    <button class="btn btn-sm btn-outline-secondary rounded-pill px-3 fw-bold" onclick="toggleSide()">
                        <i class="bi bi-arrow-left-right me-1"></i> Đảo mặt
                    </button>
                </div>
                <button class="btn btn-sm btn-link text-danger text-decoration-none fw-bold" onclick="restartLearning()">
                    <i class="bi bi-arrow-counterclockwise"></i> Học lại từ đầu
                </button>
            </div>

            <?php if (empty($words)): ?>
                <div class="alert alert-info text-center rounded-4 p-5">
                    Chủ đề này hiện chưa có từ vựng. <br>
                    <a href="flashcard.php" class="btn btn-primary mt-3 rounded-pill px-4">Xem chủ đề khác</a>
                </div>
            <?php else: ?>
                <div class="progress-bar-custom shadow-sm mb-4">
                    <div class="progress-inner" id="study-progress" style="width: 0%"></div>
                </div>

                <div class="flashcard-container mb-5" id="main-container">
                    <div class="flashcard" id="card-inner">
                        <div class="flashcard-front">
                            <div id="front-content" class="w-100">
                                <div class="kana-hint" id="hint-text"></div>
                                <div class="main-text" id="front-text"></div>
                            </div>
                            <div class="mt-4 text-muted small opacity-50"><i class="bi bi-hand-index-thumb"></i> Bấm để xem đáp án</div>
                        </div>
                        <div class="flashcard-back">
                            <div id="back-content" class="w-100">
                                <div class="main-text" id="back-text"></div>
                                <div class="example-box d-none" id="example-box">
                                    <small class="text-uppercase fw-bold opacity-75 d-block mb-1">Ví dụ:</small>
                                    <div id="example-text" class="jap-text fst-italic"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-center align-items-center gap-4">
                    <div class="text-center">
                        <button class="control-btn btn-not-mastered" onclick="notMastered()">
                            <i class="bi bi-x-lg"></i>
                        </button>
                        <div class="small mt-2 fw-bold text-warning">Chưa thuộc</div>
                    </div>
                    
                    <div class="text-center">
                        <button class="control-btn btn-mastered" onclick="mastered()">
                            <i class="bi bi-check-lg"></i>
                        </button>
                        <div class="small mt-2 fw-bold text-success">Đã thuộc</div>
                    </div>
                </div>
                
                <div class="text-center mt-4 text-muted small">
                    <span class="badge bg-light text-dark border px-3 py-2 rounded-pill">
                        <i class="bi bi-keyboard me-2"></i> Space: Lật | ← : Chưa thuộc | → : Đã thuộc
                    </span>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<script>
<<<<<<< HEAD
    const flashcards = <?php echo json_encode($flashcards); ?>;
    let currentIndex = 0;
    const cardInner = document.getElementById('card-inner');
    const frontText = document.getElementById('front-text');
    const backText = document.getElementById('back-text');
    const indexDisplay = document.getElementById('current-index');
    const prevBtn = document.getElementById('prev-btn');
    const nextBtn = document.getElementById('next-btn');

    function updateCard() {
        if (flashcards.length === 0) return;
        
        // Reset trạng thái lật trước khi đổi nội dung
        cardInner.classList.remove('is-flipped');
        
        setTimeout(() => {
            const current = flashcards[currentIndex];
            frontText.innerText = current.word;
            backText.innerHTML = `
                <div class="mb-3">
                    <small class="text-uppercase opacity-75 d-block">Cách đọc (Furigana):</small>
                    <h3 class="fw-bold">${current.reading}</h3>
                </div>
                <div class="mb-3">
                    <small class="text-uppercase opacity-75 d-block">Ý nghĩa:</small>
                    <div class="fs-5">${current.meaning}</div>
                </div>
                ${current.example ? `
                <div class="small mt-auto pt-3 border-top border-white border-opacity-25">
                    <small class="text-uppercase opacity-75 d-block">Ví dụ minh họa:</small>
                    <div class="fst-italic">${current.example}</div>
                </div>` : ''}
            `;
            indexDisplay.innerText = currentIndex + 1;
            document.getElementById('total-cards').innerText = flashcards.length;
            
            prevBtn.disabled = currentIndex === 0;
            nextBtn.disabled = currentIndex === flashcards.length - 1;
        }, 150);
    }

    function masterCard() {
        if (flashcards.length === 0) return;
        
        // Xoá thẻ hiện tại khỏi mảng
        flashcards.splice(currentIndex, 1);
        
        if (flashcards.length === 0) {
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
            if (currentIndex >= flashcards.length) {
                currentIndex = flashcards.length - 1;
=======
    // Dữ liệu gốc
    const allWords = <?php echo json_encode($words); ?>;
    // Hàng đợi học tập hiện tại
    let learningQueue = [...allWords];
    // Danh sách đã thuộc
    let masteredWords = [];
    
    let isFlippedSide = false; // Mặc định: Nhật trước - Việt sau
    
    if (allWords.length > 0) {
        const cardInner = document.getElementById('card-inner');
        const frontText = document.getElementById('front-text');
        const hintText = document.getElementById('hint-text');
        const backText = document.getElementById('back-text');
        const exampleBox = document.getElementById('example-box');
        const exampleText = document.getElementById('example-text');
        const masteredDisplay = document.getElementById('mastered-count');
        const progressInner = document.getElementById('study-progress');

        function updateCard() {
            if (learningQueue.length === 0) {
                showResult();
                return;
            }
            
            cardInner.classList.remove('is-flipped');
            
            setTimeout(() => {
                const current = learningQueue[0];
                
                if (!isFlippedSide) {
                    // Nhật trước - Việt sau
                    hintText.innerText = current.reading;
                    frontText.innerText = current.word;
                    backText.innerText = current.meaning;
                } else {
                    // Việt trước - Nhật sau
                    hintText.innerText = "";
                    frontText.innerText = current.meaning;
                    backText.innerHTML = `<div class='kana-hint mb-2' style='color:#fef08a'>${current.reading}</div>${current.word}`;
                }
                
                if (current.example && current.example.trim() !== '') {
                    exampleBox.classList.remove('d-none');
                    exampleText.innerText = current.example;
                } else {
                    exampleBox.classList.add('d-none');
                }

                // Cập nhật thống kê
                masteredDisplay.innerText = masteredWords.length;
                const progress = (masteredWords.length / allWords.length) * 100;
                progressInner.style.width = progress + '%';
            }, 100);
        }

        // Đã thuộc: Xóa khỏi queue, thêm vào mastered
        function mastered() {
            if (learningQueue.length === 0) return;
            const learned = learningQueue.shift();
            masteredWords.push(learned);
            updateCard();
        }

        // Chưa thuộc: Đẩy xuống cuối queue
        function notMastered() {
            if (learningQueue.length === 0) return;
            const current = learningQueue.shift();
            learningQueue.push(current);
            updateCard();
        }

        function shuffleQueue() {
            for (let i = learningQueue.length - 1; i > 0; i--) {
                const j = Math.floor(Math.random() * (i + 1));
                [learningQueue[i], learningQueue[j]] = [learningQueue[j], learningQueue[i]];
>>>>>>> 484deb05ac9f85d44008acad1a9ef4d65753e3b0
            }
            updateCard();
        }

<<<<<<< HEAD
    function nextCard() {
        if (currentIndex < flashcards.length - 1) {
            currentIndex++;
=======
        function toggleSide() {
            isFlippedSide = !isFlippedSide;
>>>>>>> 484deb05ac9f85d44008acad1a9ef4d65753e3b0
            updateCard();
            // Toast thông báo (có thể thêm sau)
        }

        function restartLearning() {
            learningQueue = [...allWords];
            masteredWords = [];
            updateCard();
            // Reset giao diện nếu đã hiện kết quả
            location.reload(); 
        }

        function showResult() {
            document.getElementById('main-container').innerHTML = `
                <div class="text-center p-5 bg-white rounded-4 shadow-sm border border-success border-2 animate__animated animate__fadeIn">
                    <div class="display-1 text-success mb-3"><i class="bi bi-trophy"></i></div>
                    <h2 class="fw-bold mb-3">Tuyệt vời!</h2>
                    <p class="text-muted mb-4 fs-5">Bạn đã thuộc toàn bộ <b>${allWords.length}</b> từ vựng trong chủ đề này.</p>
                    <div class="d-grid gap-2 d-sm-flex justify-content-center">
                        <button class="btn btn-primary rounded-pill px-5 fw-bold" onclick="location.reload()">Học lại chủ đề này</button>
                        <a href="flashcard.php" class="btn btn-outline-secondary rounded-pill px-5 fw-bold">Học chủ đề khác</a>
                    </div>
                </div>
            `;
            const controls = document.querySelector('.d-flex.justify-content-center.gap-4');
            if (controls) controls.style.display = 'none';
        }

        cardInner.addEventListener('click', () => cardInner.classList.toggle('is-flipped'));

        // Phím tắt
        document.addEventListener('keydown', (e) => {
            if (e.code === 'Space') { e.preventDefault(); cardInner.click(); }
            if (e.code === 'ArrowRight') mastered();
            if (e.code === 'ArrowLeft') notMastered();
        });

        // Khởi tạo
        updateCard();
    }
<<<<<<< HEAD

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
    if (flashcards.length > 0) updateCard();
=======
>>>>>>> 484deb05ac9f85d44008acad1a9ef4d65753e3b0
</script>





<?php include 'includes/footer.php'; ?>