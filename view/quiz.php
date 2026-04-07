<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Nếu chưa chọn level thì hiển thị form chọn cấp độ
$levels = ['N5', 'N4', 'N3', 'N2'];
$level = isset($_GET['level']) ? $_GET['level'] : null;

if (!$level || !in_array($level, $levels)) {
    // Hiển thị giao diện chọn cấp độ
    ?>
    <?php include 'includes/header.php'; ?>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8 text-center">
                <h2 class="mb-4 fw-bold">Chọn cấp độ JLPT</h2>
                <p class="text-muted mb-5">Hãy chọn cấp độ phù hợp để bắt đầu bài thi thử.</p>
                <div class="d-flex justify-content-center flex-wrap gap-3">
                    <?php foreach ($levels as $lv): ?>
                        <a href="?level=<?php echo $lv; ?>" class="btn btn-outline-primary btn-lg px-5 py-3 fw-bold shadow-sm" style="border-radius: 15px;"><?php echo $lv; ?></a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    <?php include 'includes/footer.php'; ?>
    <?php
    exit;
}

try {
    $dbHost = '127.0.0.1';
    $dbName = 'jlpt_ai_learning';
    $dbUser = 'root';
    $dbPass = '';

    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4", $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    // Lấy ngẫu nhiên 10 câu hỏi theo level
    $stmt = $pdo->prepare("SELECT * FROM questions WHERE level = ? ORDER BY RAND() LIMIT 10");
    $stmt->execute([$level]);
    $questions = $stmt->fetchAll();
} catch (PDOException $e) {
    die('Lỗi kết nối CSDL: ' . $e->getMessage());
}
?>
<?php include 'includes/header.php'; ?>
<style>
    .question-card { border-left: 5px solid var(--primary-color); }
    .option-container { 
        cursor: pointer; 
        border: 2px solid #f1f5f9; 
        border-radius: 12px; 
        padding: 12px 20px; 
        transition: all 0.2s ease;
    }
    .option-container:hover { background-color: #f8fafc; border-color: var(--primary-color); }
    .form-check-input:checked + .option-label { color: var(--primary-color); font-weight: 700; }
    .progress { height: 8px; background-color: #e2e8f0; }
    .progress-bar { background: var(--primary-gradient); }
    .sticky-progress { position: sticky; top: 0; z-index: 1000; background: rgba(248, 250, 252, 0.9); backdrop-filter: blur(10px); padding: 15px 0; }
</style>

<div class="container pb-5">
    <div class="sticky-progress">
        <div class="d-flex justify-content-between mb-1 small fw-bold">
            <span>Tiến độ làm bài</span>
            <span id="progress-text">0/<?php echo count($questions); ?></span>
        </div>
        <div class="progress">
            <div id="quiz-progress" class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%"></div>
        </div>
    </div>

    <h2 class="my-4 text-center fw-bold">JLPT <?php echo htmlspecialchars($level); ?> 模擬テスト</h2>
    
    <form action="score.php" method="POST" id="quizForm">
        <input type="hidden" name="level" value="<?php echo htmlspecialchars($level); ?>">
        <?php if (empty($questions)): ?>
            <div class="alert alert-warning shadow-sm">Không tìm thấy câu hỏi nào cho trình độ này.</div>
        <?php else: ?>
            <?php foreach ($questions as $idx => $q): ?>
                <div class="card question-card shadow-sm mb-4">
                    <div class="card-body p-4">
                        <h5 class="card-title mb-4">
                            <span class="badge bg-primary rounded-pill me-2">Câu <?php echo $idx+1; ?></span>
                            <span class="lh-base"><?php echo htmlspecialchars($q['content']); ?></span>
                        </h5>
                        <div class="row g-3">
                            <?php foreach(['A','B','C','D'] as $opt): ?>
                                <div class="col-12 col-md-6">
                                    <label class="option-container d-block w-100 mb-0" for="q<?php echo $q['id'].$opt; ?>">
                                        <div class="form-check">
                                            <input class="form-check-input quiz-answer" type="radio" name="answers[<?php echo $q['id']; ?>]" id="q<?php echo $q['id'].$opt; ?>" value="<?php echo $opt; ?>" required>
                                            <span class="option-label ms-2"><?php echo htmlspecialchars($q['option_'.strtolower($opt)]); ?></span>
                                        </div>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            <div class="text-center">
                <button type="submit" class="btn btn-primary btn-lg px-5">Nộp bài</button>
            </div>
        <?php endif; ?>
    </form>
</div>

<script>
document.querySelectorAll('.quiz-answer').forEach(input => {
    input.addEventListener('change', () => {
        const total = <?php echo count($questions); ?>;
        const answered = document.querySelectorAll('.quiz-answer:checked').length;
        const percent = (answered / total) * 100;
        document.getElementById('quiz-progress').style.width = percent + '%';
        document.getElementById('progress-text').innerText = answered + '/' + total;
    });
});
</script>

<?php include 'includes/footer.php'; ?>
