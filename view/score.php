<?php
// score.php: Chấm điểm và lưu kết quả vào exam_results, result_details
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
require_once 'db.php';

// Lấy dữ liệu từ form
$level = $_POST['level'] ?? '';
$answers = $_POST['answers'] ?? [];
$user_id = $_SESSION['user_id'];

$total = count($answers);
$total_correct = 0;
$total_incorrect = 0;
$details = [];

// Chấm điểm từng câu
foreach ($answers as $qid => $user_answer) {
    $stmt = $pdo->prepare("SELECT correct_answer FROM questions WHERE id = ?");
    $stmt->execute([$qid]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) continue;
    $is_correct = ($user_answer === $row['correct_answer']);
    if ($is_correct) $total_correct++; else $total_incorrect++;
    $details[] = [
        'question_id' => $qid,
        'user_answer' => $user_answer,
        'is_correct' => $is_correct ? 1 : 0
    ];
}
$score = $total ? round($total_correct / $total * 100, 2) : 0;

// Lưu vào exam_results
$stmt = $pdo->prepare("INSERT INTO exam_results (user_id, score, total_correct, total_incorrect, exam_date) VALUES (?, ?, ?, ?, NOW())");
$stmt->execute([$user_id, $score, $total_correct, $total_incorrect]);
$result_id = $pdo->lastInsertId();

// Lưu từng câu vào result_details
$stmt = $pdo->prepare("INSERT INTO result_details (result_id, question_id, user_answer, is_correct) VALUES (?, ?, ?, ?)");
foreach ($details as $d) {
    $stmt->execute([$result_id, $d['question_id'], $d['user_answer'], $d['is_correct']]);
}
?>
<?php include 'includes/header.php'; ?>

<style>
    .result-card { border-radius: 16px; border: none; overflow: hidden; }
    .stat-card { border-radius: 20px; background: white; border: none; }
    .correct-border { border-left: 5px solid #198754 !important; }
    .incorrect-border { border-left: 5px solid #dc3545 !important; }
    .option-item { padding: 10px 15px; border-radius: 10px; margin-bottom: 8px; font-size: 0.95rem; border: 1px solid #f1f5f9; }
    .option-correct { background-color: #d1e7dd; color: #0f5132; font-weight: 600; }
    .option-user-wrong { background-color: #f8d7da; color: #842029; text-decoration: line-through; }
    .explanation-box { background-color: #f0fdf4; border: 1px solid #dcfce7; color: #166534; border-radius: 12px; padding: 20px; margin-top: 15px; }
</style>

<div class="container py-4">
    <div class="row justify-content-center mb-5">
        <div class="col-md-8 text-center">
            <h2 class="fw-bold mb-4">Kết quả bài thi JLPT <?php echo htmlspecialchars($level); ?></h2>
            
            <div class="card stat-card shadow-sm mb-4">
                <div class="card-body p-4">
                    <div class="row align-items-center">
                        <div class="col-md-4">
                            <div class="display-4 fw-bold text-primary"><?php echo $score; ?><span class="fs-4">/100</span></div>
                            <div class="text-muted small fw-bold text-uppercase">Điểm số</div>
                        </div>
                        <div class="col-md-8 border-start">
                            <div class="d-flex justify-content-around">
                                <div>
                                    <div class="h3 mb-0 text-success fw-bold"><?php echo $total_correct; ?></div>
                                    <div class="text-muted small">Câu đúng</div>
                                </div>
                                <div>
                                    <div class="h3 mb-0 text-danger fw-bold"><?php echo $total_incorrect; ?></div>
                                    <div class="text-muted small">Câu sai</div>
                                </div>
                                <div>
                                    <div class="h3 mb-0 text-info fw-bold"><?php echo $total; ?></div>
                                    <div class="text-muted small">Tổng số câu</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <a href="quiz.php?level=<?php echo urlencode($level); ?>" class="btn btn-primary btn-lg px-5 shadow-sm" style="border-radius: 30px;">Làm đề khác</a>
        </div>
    </div>

    <h4 class="fw-bold mb-4"><i class="bi bi-list-check"></i> Xem lại chi tiết</h4>
    
    <div class="row justify-content-center">
        <div class="col-md-10">
            <?php foreach ($details as $idx => $d): 
                $stmt = $pdo->prepare("SELECT content, option_a, option_b, option_c, option_d, correct_answer, explanation FROM questions WHERE id = ?");
                $stmt->execute([$d['question_id']]);
                $q = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$q) continue;
                $isCorrect = $d['is_correct'];
            ?>
                <div class="card result-card shadow-sm mb-4 <?php echo $isCorrect ? 'correct-border' : 'incorrect-border'; ?>">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <h5 class="fw-bold mb-0">Câu <?php echo $idx + 1; ?>: <?php echo htmlspecialchars($q['content']); ?></h5>
                            <span class="badge <?php echo $isCorrect ? 'bg-success' : 'bg-danger'; ?> rounded-pill px-3">
                                <?php echo $isCorrect ? 'Đúng' : 'Sai'; ?>
                            </span>
                        </div>

                        <div class="row mt-3">
                            <?php foreach(['A','B','C','D'] as $opt): 
                                $optText = $q['option_'.strtolower($opt)];
                                $class = "";
                                if ($opt === $q['correct_answer']) $class = "option-correct";
                                if (!$isCorrect && $opt === $d['user_answer']) $class = "option-user-wrong";
                            ?>
                                <div class="col-md-6">
                                    <div class="option-item <?php echo $class; ?>">
                                        <strong><?php echo $opt; ?>.</strong> <?php echo htmlspecialchars($optText); ?>
                                        <?php if ($opt === $q['correct_answer']): ?> <small class="ms-2">(Đáp án đúng)</small> <?php endif; ?>
                                        <?php if (!$isCorrect && $opt === $d['user_answer']): ?> <small class="ms-2">(Lựa chọn của bạn)</small> <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <?php if ($q['explanation']): ?>
                            <div class="explanation-box">
                                <strong><i class="bi bi-info-circle"></i> Giải thích:</strong><br>
                                <?php echo htmlspecialchars($q['explanation']); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
