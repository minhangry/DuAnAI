<?php
/**
 * import_flashcards.php
 * Script để import dữ liệu từ flashcard.json vào bảng flashcards
 */
require_once 'view/db.php';
$jsonPath = __DIR__ . '/flashcard.json';

if (!file_exists($jsonPath)) {
    die("Không tìm thấy file: $jsonPath");
}

$json = file_get_contents($jsonPath);
$data = json_decode($json, true);
if (!is_array($data)) {
    die("Lỗi định dạng JSON hoặc file trống.");
}

$sql = "INSERT INTO flashcards (theme, word, reading, meaning, example, level, created_at)
        VALUES (:theme, :word, :reading, :meaning, :example, :level, NOW())";
$stmt = $pdo->prepare($sql);

$inserted = 0;
foreach ($data as $themeGroup) {
    $theme = $themeGroup['theme'] ?? 'Chưa phân loại';
    $vocabList = $themeGroup['vocabulary'] ?? [];
    
    foreach ($vocabList as $v) {
        // Kiểm tra các trường bắt buộc
        if (!isset($v['word'], $v['reading'], $v['meaning'])) continue;
        
        $stmt->execute([
            ':theme'   => $theme,
            ':word'    => $v['word'],
            ':reading' => $v['reading'],
            ':meaning' => $v['meaning'],
            ':example' => $v['example'] ?? null,
            ':level'   => 'N3' // Mặc định là N3 theo dữ liệu hiện tại
        ]);
        $inserted++;
    }
}

echo "Đã thêm thành công $inserted thẻ flashcard vào database!\n";
?>