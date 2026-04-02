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
    <!DOCTYPE html>
    <html lang="ja">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Chọn cấp độ JLPT</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
        <style>
            body { background: #f8f9fa; font-family: 'Noto Sans JP', sans-serif; }
            .level-btn { font-size: 1.5rem; border-radius: 2rem; margin: 0.5rem; min-width: 120px; }
        </style>
        <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@400;700&display=swap" rel="stylesheet">
    </head>
    <body>
    <div class="container py-5">
        <h2 class="mb-4 text-center">Chọn cấp độ JLPT bạn muốn luyện thi</h2>
        <div class="d-flex justify-content-center flex-wrap">
            <?php foreach ($levels as $lv): ?>
                <a href="?level=<?php echo $lv; ?>" class="btn btn-primary level-btn"><?php echo $lv; ?></a>
            <?php endforeach; ?>
        </div>
    </div>
    </body>
    </html>
    <?php
    exit;
}

// Kết nối database luyen_thi_jlpt
$host = 'localhost';
$user = 'root';
$password = '';
$dbname = 'jlpt_ai_learning';
$conn = new mysqli($host, $user, $password, $dbname);
if ($conn->connect_error) {
    die('Kết nối thất bại: ' . $conn->connect_error);
}
// Lấy ngẫu nhiên 10 câu hỏi theo level
$sql = "SELECT * FROM questions WHERE level = ? ORDER BY RAND() LIMIT 10";
$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $level);
$stmt->execute();
$result = $stmt->get_result();
$questions = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Quiz JLPT <?php echo htmlspecialchars($level); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; font-family: 'Noto Sans JP', sans-serif; }
        .question-card { border-radius: 1rem; box-shadow: 0 2px 8px #e0e0e0; margin-bottom: 2rem; }
        .question-title { font-weight: 600; font-size: 1.1rem; }
        .option-label { font-weight: 400; letter-spacing: 0.5px; }
        .btn-primary { border-radius: 2rem; font-weight: 600; }
    </style>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@400;700&display=swap" rel="stylesheet">
</head>
<body>
<div class="container py-4">
    <h2 class="mb-4 text-center">JLPT <?php echo htmlspecialchars($level); ?> 模擬テスト</h2>
    <form action="score.php" method="POST">
        <input type="hidden" name="level" value="<?php echo htmlspecialchars($level); ?>">
        <?php if (empty($questions)): ?>
            <div class="alert alert-warning">Không tìm thấy câu hỏi nào cho trình độ này.</div>
        <?php else: ?>
            <?php foreach ($questions as $idx => $q): ?>
                <div class="card question-card">
                    <div class="card-body">
                        <div class="question-title mb-3">
                            <span class="badge bg-secondary me-2">Câu <?php echo $idx+1; ?></span>
                            <?php echo htmlspecialchars($q['content']); ?>
                        </div>
                        <div class="row g-2">
                            <?php foreach(['A','B','C','D'] as $opt): ?>
                                <div class="col-12 col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="answers[<?php echo $q['id']; ?>]" id="q<?php echo $q['id'].$opt; ?>" value="<?php echo $opt; ?>">
                                        <label class="form-check-label option-label" for="q<?php echo $q['id'].$opt; ?>">
                                            <?php echo htmlspecialchars($q['option_'.strtolower($opt)]); ?>
                                        </label>
                                    </div>
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
</body>
</html>
