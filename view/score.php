<?php
// score.php: Chấm điểm và lưu kết quả vào exam_results, result_details
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$dbHost = '127.0.0.1';
$dbName = 'jlpt_ai_learning';
$dbUser = 'root';
$dbPass = '';

// Lấy dữ liệu từ form
$level = $_POST['level'] ?? '';
$answers = $_POST['answers'] ?? [];
$user_id = $_SESSION['user_id'] ?? 1; // Demo: lấy user_id từ session, nếu chưa có thì gán tạm là 1

try {
    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4", $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    die("DB connect error: " . $e->getMessage());
}

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
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Kết quả bài thi JLPT</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container py-4">
    <h2 class="mb-4 text-center">Kết quả bài thi thử JLPT <?php echo htmlspecialchars($level); ?></h2>
    <div class="alert alert-info text-center fs-4">Điểm số: <strong><?php echo $score; ?></strong> / 100</div>
    <div class="mb-3 text-center">Số câu đúng: <b><?php echo $total_correct; ?></b> / <?php echo $total; ?> | Số câu sai: <b><?php echo $total_incorrect; ?></b></div>
    <div class="text-center">
        <a href="quiz.php?level=<?php echo urlencode($level); ?>" class="btn btn-primary">Làm lại đề khác</a>
    </div>
    <hr>
    <h4>Chi tiết từng câu:</h4>
    <ol>
    <?php
    $i = 1;
    foreach ($details as $d) {
        $stmt = $pdo->prepare("SELECT content, option_a, option_b, option_c, option_d, correct_answer FROM questions WHERE id = ?");
        $stmt->execute([$d['question_id']]);
        $q = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$q) continue;
        echo '<li class="mb-3">';
        echo '<div><b>Câu hỏi:</b> ' . htmlspecialchars($q['content']) . '</div>';
        foreach(['A','B','C','D'] as $opt) {
            $opt_text = $q['option_'.strtolower($opt)];
            $userMark = ($d['user_answer'] === $opt) ? ' <span class="badge bg-warning text-dark">Bạn chọn</span>' : '';
            $correctMark = ($q['correct_answer'] === $opt) ? ' <span class="badge bg-success">Đáp án đúng</span>' : '';
            echo '<div>' . $opt . '. ' . htmlspecialchars($opt_text) . $userMark . $correctMark . '</div>';
        }
        echo '<div>Kết quả: ' . ($d['is_correct'] ? '<span class="text-success">Đúng</span>' : '<span class="text-danger">Sai</span>') . '</div>';
        echo '</li>';
        $i++;
    }
    ?>
    </ol>
</div>
</body>
</html>
